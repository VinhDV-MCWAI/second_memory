<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Services\Media\ObjectStorage;

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

    public function put(string $key, int $size, string $mimeType = 'application/octet-stream'): void
    {
        $this->objects[$key] = ['size' => $size, 'etag' => md5($key), 'mime_type' => $mimeType];
    }
}
