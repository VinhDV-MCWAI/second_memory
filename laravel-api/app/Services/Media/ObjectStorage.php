<?php

declare(strict_types=1);

namespace App\Services\Media;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Support\Str;

/**
 * The MinIO bucket of the file manager (ADR-0014). Server calls go over the Docker network; presigned
 * URLs are signed for the public host (nginx), because the signature covers the host the browser sends.
 * Not final: feature tests swap it for an in-memory fake.
 */
class ObjectStorage
{
    private const HTTP_NOT_FOUND = 404;

    private ?S3Client $internalClient = null;

    private ?S3Client $publicClient = null;

    public function bucket(): string
    {
        return (string) config('media.bucket');
    }

    public function presignedPutUrl(string $key, string $contentType, int $ttlSeconds): string
    {
        $command = $this->publicClient()->getCommand('PutObject', [
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'ContentType' => $contentType,
        ]);

        return (string) $this->publicClient()->createPresignedRequest($command, "+{$ttlSeconds} seconds")->getUri();
    }

    /**
     * A download, or with $inlineContentType a preview the browser shows itself, served with that type.
     */
    public function presignedGetUrl(string $key, string $downloadName, int $ttlSeconds, ?string $inlineContentType = null): string
    {
        $command = $this->publicClient()->getCommand('GetObject', array_filter([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'ResponseContentDisposition' => $this->disposition($inlineContentType === null ? 'attachment' : 'inline', $downloadName),
            'ResponseContentType' => $inlineContentType,
        ]));

        return (string) $this->publicClient()->createPresignedRequest($command, "+{$ttlSeconds} seconds")->getUri();
    }

    /** Starts an S3 multipart upload; the parts go up with presignedPartUrl(), then complete or abort. */
    public function createMultipartUpload(string $key, string $contentType): string
    {
        $result = $this->internalClient()->createMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'ContentType' => $contentType,
        ]);

        return (string) $result['UploadId'];
    }

    public function presignedPartUrl(string $key, string $uploadId, int $partNumber, int $ttlSeconds): string
    {
        $command = $this->publicClient()->getCommand('UploadPart', [
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        return (string) $this->publicClient()->createPresignedRequest($command, "+{$ttlSeconds} seconds")->getUri();
    }

    /**
     * Joins the parts into one object. MinIO checks every ETag; a missing or wrong part fails the call.
     *
     * @param  list<array{part_number: int, etag: string}>  $parts
     */
    public function completeMultipartUpload(string $key, string $uploadId, array $parts): void
    {
        usort($parts, fn (array $a, array $b): int => $a['part_number'] <=> $b['part_number']);

        $this->internalClient()->completeMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => array_map(fn (array $part): array => ['PartNumber' => $part['part_number'], 'ETag' => $part['etag']], $parts),
            ],
        ]);
    }

    /**
     * Parts MinIO already holds for an unfinished upload, or null when the upload is gone.
     *
     * @return list<array{part_number: int, etag: string, size: int}>|null
     */
    public function listParts(string $key, string $uploadId): ?array
    {
        $parts = [];
        $marker = 0;
        do {
            try {
                $result = $this->internalClient()->listParts(['Bucket' => $this->bucket(), 'Key' => $key, 'UploadId' => $uploadId, 'PartNumberMarker' => $marker]);
            } catch (S3Exception $e) {
                if ($e->getStatusCode() === self::HTTP_NOT_FOUND) {
                    return null;
                }
                throw $e;
            }
            foreach ($result['Parts'] ?? [] as $part) {
                $parts[] = ['part_number' => (int) $part['PartNumber'], 'etag' => trim((string) $part['ETag'], '"'), 'size' => (int) $part['Size']];
            }
            $marker = (int) ($result['NextPartNumberMarker'] ?? 0);
        } while ((bool) ($result['IsTruncated'] ?? false));

        return $parts;
    }

    /** Drops the uploaded parts; an upload that is already gone (finished, aborted, expired) is fine. */
    public function abortMultipartUpload(string $key, string $uploadId): void
    {
        try {
            $this->internalClient()->abortMultipartUpload(['Bucket' => $this->bucket(), 'Key' => $key, 'UploadId' => $uploadId]);
        } catch (S3Exception $e) {
            if ($e->getStatusCode() !== self::HTTP_NOT_FOUND) {
                throw $e;
            }
        }
    }

    /**
     * Size, ETag and content type of a stored object, or null when it does not exist.
     *
     * @return array{size: int, etag: string, mime_type: string|null}|null
     */
    public function head(string $key): ?array
    {
        try {
            $result = $this->internalClient()->headObject(['Bucket' => $this->bucket(), 'Key' => $key]);
        } catch (S3Exception $e) {
            if ($e->getStatusCode() === self::HTTP_NOT_FOUND) {
                return null;
            }
            throw $e;
        }

        return [
            'size' => (int) $result['ContentLength'],
            'etag' => trim((string) $result['ETag'], '"'),
            'mime_type' => $result['ContentType'] ?? null,
        ];
    }

    /** Deleting a missing object succeeds; on the versioned bucket it leaves a delete marker (30 days to restore). */
    public function delete(string $key): void
    {
        $this->internalClient()->deleteObject(['Bucket' => $this->bucket(), 'Key' => $key]);
    }

    /** A deleted object can come back: some version of it is still kept (not yet expired or purged). */
    public function restorable(string $key): bool
    {
        return $this->versionsOf($key)['versions'] !== [];
    }

    /** Undoes a delete on the versioned bucket: drops the delete marker, so the last version is current again. */
    public function restore(string $key): void
    {
        foreach ($this->versionsOf($key)['markers'] as $versionId) {
            $this->internalClient()->deleteObject(['Bucket' => $this->bucket(), 'Key' => $key, 'VersionId' => $versionId]);
        }
    }

    /** Deletes every version and delete marker: nothing of the object is left to restore. */
    public function purge(string $key): void
    {
        $versions = $this->versionsOf($key);
        foreach ([...$versions['versions'], ...$versions['markers']] as $versionId) {
            $this->internalClient()->deleteObject(['Bucket' => $this->bucket(), 'Key' => $key, 'VersionId' => $versionId]);
        }
    }

    /**
     * Version ids of one key: stored versions and delete markers.
     *
     * @return array{versions: list<string>, markers: list<string>}
     */
    private function versionsOf(string $key): array
    {
        $found = ['versions' => [], 'markers' => []];
        $params = ['Bucket' => $this->bucket(), 'Prefix' => $key];
        do {
            $result = $this->internalClient()->listObjectVersions($params);
            foreach (['Versions' => 'versions', 'DeleteMarkers' => 'markers'] as $field => $kind) {
                foreach ($result[$field] ?? [] as $entry) {
                    if ($entry['Key'] === $key) {
                        $found[$kind][] = (string) $entry['VersionId'];
                    }
                }
            }
            $params['KeyMarker'] = $result['NextKeyMarker'] ?? null;
            $params['VersionIdMarker'] = $result['NextVersionIdMarker'] ?? null;
        } while ((bool) ($result['IsTruncated'] ?? false));

        return $found;
    }

    private function internalClient(): S3Client
    {
        return $this->internalClient ??= $this->client((string) config('media.endpoint'));
    }

    private function publicClient(): S3Client
    {
        return $this->publicClient ??= $this->client((string) config('media.public_endpoint'));
    }

    private function client(string $endpoint): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => config('media.region'),
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => ['key' => config('media.key'), 'secret' => config('media.secret')],
        ]);
    }

    /** RFC 6266: an ASCII fallback plus the exact UTF-8 name. */
    private function disposition(string $type, string $name): string
    {
        $fallback = str_replace(['"', '\\'], '', Str::ascii($name));

        return sprintf('%s; filename="%s"; filename*=UTF-8\'\'%s', $type, $fallback, rawurlencode($name));
    }
}
