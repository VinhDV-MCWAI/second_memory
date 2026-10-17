<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Constants\MediaConst;
use App\Models\Media\MediaMgmt;
use App\Services\Media\ObjectStorage;
use Tests\Fakes\FakeObjectStorage;

/**
 * Puts files into the file manager through its API, with MinIO replaced by FakeObjectStorage.
 * Call useFakeStorage() in setUp().
 */
trait UploadsFiles
{
    private FakeObjectStorage $storage;

    private function useFakeStorage(): void
    {
        $this->storage = new FakeObjectStorage;
        $this->app->instance(ObjectStorage::class, $this->storage);
    }

    /**
     * Uploads a small file (into the root folder by default) and returns its id.
     *
     * @param  array<string, string>  $cookies  session of an admin allowed to upload
     */
    private function uploadFile(array $cookies, string $name, int $size = 10, string $folder = MediaConst::ROOT): int
    {
        $id = $this->call('POST', '/api/admin/media/upload-url', ['folder' => $folder, 'name' => $name, 'size' => $size, 'mime_type' => 'application/octet-stream'], $cookies)
            ->assertOk()->json('data.id');
        $this->storage->put((string) MediaMgmt::query()->findOrFail($id)->minio_object_key, $size);
        $this->call('POST', "/api/admin/media/complete/{$id}", [], $cookies)->assertOk();

        return $id;
    }
}
