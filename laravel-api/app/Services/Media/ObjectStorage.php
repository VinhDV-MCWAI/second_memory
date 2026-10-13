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

    public function presignedGetUrl(string $key, string $downloadName, int $ttlSeconds): string
    {
        $command = $this->publicClient()->getCommand('GetObject', [
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'ResponseContentDisposition' => $this->attachment($downloadName),
        ]);

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
    private function attachment(string $name): string
    {
        $fallback = str_replace(['"', '\\'], '', Str::ascii($name));

        return sprintf('attachment; filename="%s"; filename*=UTF-8\'\'%s', $fallback, rawurlencode($name));
    }
}
