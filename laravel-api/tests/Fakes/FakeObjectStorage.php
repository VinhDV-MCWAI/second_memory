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

    public function presignedGetUrl(string $key, string $downloadName, int $ttlSeconds, ?string $inlineContentType = null): string
    {
        $inline = $inlineContentType === null ? '' : '&inline='.rawurlencode($inlineContentType);

        return "http://storage.test/media-test/{$key}?X-Amz-Expires={$ttlSeconds}&name=".rawurlencode($downloadName).$inline;
    }

    public function head(string $key): ?array
    {
        return $this->objects[$key] ?? null;
    }

    /** @var array<string, string> contents given with putBody(); other objects read as zero bytes */
    public array $bodies = [];

    public function readStart(string $key, int $bytes): string
    {
        return substr($this->bodies[$key] ?? str_repeat("\0", $this->objects[$key]['size'] ?? 0), 0, $bytes);
    }

    public function putBody(string $key, string $body): void
    {
        $this->put($key, strlen($body));
        $this->bodies[$key] = $body;
    }

    /** @var array<string, array{size: int, etag: string, mime_type: string|null}> deleted objects the versioned bucket still keeps */
    public array $kept = [];

    /** @var list<string> */
    public array $purged = [];

    public function delete(string $key): void
    {
        if (isset($this->objects[$key])) {
            $this->kept[$key] = $this->objects[$key];
        }
        unset($this->objects[$key]);
        $this->deleted[] = $key;
    }

    public function restorable(string $key): bool
    {
        return isset($this->kept[$key]);
    }

    public function restore(string $key): void
    {
        $this->objects[$key] = $this->kept[$key];
        unset($this->kept[$key]);
    }

    public function purge(string $key): void
    {
        unset($this->kept[$key], $this->objects[$key]);
        $this->purged[] = $key;
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

    public function listParts(string $key, string $uploadId): ?array
    {
        if (! isset($this->multipart[$uploadId])) {
            return null;
        }
        $parts = [];
        foreach ($this->multipart[$uploadId]['parts'] as $number => $size) {
            $parts[] = ['part_number' => $number, 'etag' => "etag-{$number}", 'size' => $size];
        }

        return $parts;
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
