<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Services\Media\ObjectStorage;
use Aws\Command;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Psr7\Response;

/**
 * In-memory bucket for feature tests: "uploads" are put() calls, presigned URLs are fake strings.
 */
final class FakeObjectStorage extends ObjectStorage
{
    /** @var array<string, array{size: int, etag: string, mime_type: string|null}> */
    public array $objects = [];

    /** @var list<string> */
    public array $deleted = [];

    public function bucket(): string
    {
        return 'media-test';
    }

    public function presignedPutUrl(string $key, string $contentType, int $ttlSeconds): string
    {
        return "http://storage.test/media-test/{$key}?X-Amz-Expires={$ttlSeconds}";
    }

    public function presignedGetUrl(string $key, string $downloadName, int $ttlSeconds): string
    {
        return "http://storage.test/media-test/{$key}?X-Amz-Expires={$ttlSeconds}&name=".rawurlencode($downloadName);
    }

    public function head(string $key): ?array
    {
        return $this->objects[$key] ?? null;
    }

    public function delete(string $key): void
    {
        unset($this->objects[$key]);
        $this->deleted[] = $key;
    }

    /** @var array<string, array{key: string, parts: array<int, int>}> uploadId => uploaded part sizes */
    public array $multipart = [];

    /** @var list<string> */
    public array $aborted = [];

    public function createMultipartUpload(string $key, string $contentType): string
    {
        $uploadId = 'upload-'.(count($this->multipart) + 1);
        $this->multipart[$uploadId] = ['key' => $key, 'parts' => []];

        return $uploadId;
    }

    public function presignedPartUrl(string $key, string $uploadId, int $partNumber, int $ttlSeconds): string
    {
        return "http://storage.test/media-test/{$key}?uploadId={$uploadId}&partNumber={$partNumber}&X-Amz-Expires={$ttlSeconds}";
    }

    public function completeMultipartUpload(string $key, string $uploadId, array $parts): void
    {
        $upload = $this->multipart[$uploadId] ?? throw $this->s3Error('NoSuchUpload', 404);
        foreach ($parts as $part) {
            if (! isset($upload['parts'][$part['part_number']]) || $part['etag'] !== "etag-{$part['part_number']}") {
                throw $this->s3Error('InvalidPart', 400);
            }
        }
        unset($this->multipart[$uploadId]);
        $this->put($key, array_sum($upload['parts']));
    }

    public function abortMultipartUpload(string $key, string $uploadId): void
    {
        unset($this->multipart[$uploadId]);
        $this->aborted[] = $uploadId;
    }

    /** What the browser does with a part URL; the fake ETag is `etag-{partNumber}`. */
    public function uploadPart(string $uploadId, int $partNumber, int $size): string
    {
        $this->multipart[$uploadId]['parts'][$partNumber] = $size;

        return "etag-{$partNumber}";
    }

    public function put(string $key, int $size, string $mimeType = 'application/octet-stream'): void
    {
        $this->objects[$key] = ['size' => $size, 'etag' => md5($key), 'mime_type' => $mimeType];
    }

    private function s3Error(string $code, int $status): S3Exception
    {
        return new S3Exception($code, new Command('CompleteMultipartUpload'), ['code' => $code, 'response' => new Response($status)]);
    }
}
