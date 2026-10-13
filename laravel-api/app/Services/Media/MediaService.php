<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Constants\MediaConst;
use App\Constants\Messages;
use App\Enums\AuditEvent;
use App\Enums\UploadStatus;
use App\Models\Media\MediaMgmt;
use App\Repositories\Media\MediaRepository;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * File manager (ADR-0014): the browser sends bytes straight to MinIO through presigned URLs;
 * this service checks the inputs, hands out the URLs and keeps `media_mgmt` in step.
 */
final class MediaService
{
    private const AUDIT_TYPE = 'media';

    public function __construct(
        private readonly MediaRepository $media,
        private readonly ObjectStorage $storage,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return LengthAwarePaginator<int, MediaMgmt>
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $folder = $this->existingFolder($payload['folder'] ?? MediaConst::ROOT);

        return $this->media->list($folder, $payload);
    }

    /**
     * @param  array{folder?: string|null, name: string}  $payload
     */
    public function storeFolder(array $payload): int
    {
        $path = $this->freePath($payload['folder'] ?? MediaConst::ROOT, $payload['name']);

        $folder = $this->saveUnique(fn (): MediaMgmt => $this->media->create([
            'is_file' => false,
            'virtual_path' => $path,
            'original_name' => $payload['name'],
            'upload_status' => UploadStatus::COMPLETED,
            'created_by' => Auth::id(),
        ]));
        $this->auditLogger->record(self::AUDIT_TYPE, $folder->id, AuditEvent::CREATED, null, $this->auditValues($folder));

        return $folder->id;
    }

    /**
     * Creates the row in "uploading" state and a presigned PUT URL for the browser.
     *
     * @param  array{folder?: string|null, name: string, size: int, mime_type: string}  $payload
     * @return array{id: int, url: string, method: string, headers: array{'Content-Type': string}, expires_at: string}
     */
    public function createUpload(array $payload): array
    {
        $path = $this->freePath($payload['folder'] ?? MediaConst::ROOT, $payload['name']);
        $extension = Str::lower(pathinfo($payload['name'], PATHINFO_EXTENSION));
        $key = now()->format('Y/m').'/'.Str::uuid()->toString().($extension === '' ? '' : '.'.$extension);

        $file = $this->media->create([
            'is_file' => true,
            'virtual_path' => $path,
            'original_name' => $payload['name'],
            'extension' => $extension === '' ? null : $extension,
            'mime_type' => $payload['mime_type'],
            'size' => $payload['size'],
            'minio_bucket' => $this->storage->bucket(),
            'minio_object_key' => $key,
            'upload_status' => UploadStatus::UPLOADING,
            'created_by' => Auth::id(),
        ]);

        return [
            'id' => $file->id,
            'url' => $this->storage->presignedPutUrl($key, $payload['mime_type'], MediaConst::UPLOAD_URL_TTL_SECONDS),
            'method' => 'PUT',
            'headers' => ['Content-Type' => $payload['mime_type']],
            'expires_at' => now()->addSeconds(MediaConst::UPLOAD_URL_TTL_SECONDS)->toIso8601String(),
        ];
    }

    /**
     * The browser reports the PUT finished: check the object really is there with the declared size.
     *
     * @throws ValidationException when the object is missing, has another size, or the name was taken meanwhile
     */
    public function completeUpload(int $id): MediaMgmt
    {
        $file = $this->liveFile($id);
        if ($file->upload_status !== UploadStatus::UPLOADING) {
            throw ValidationException::withMessages(['id' => Messages::E0032]);
        }

        $key = (string) $file->minio_object_key;
        $object = $this->storage->head($key);
        if ($object === null) {
            throw ValidationException::withMessages(['id' => Messages::E0030]);
        }
        if ($object['size'] !== $file->size) {
            // A different body than the one declared: drop it, the client has to start again
            $this->storage->delete($key);
            throw ValidationException::withMessages(['id' => Messages::E0031]);
        }

        $file->fill([
            'minio_etag' => $object['etag'],
            'mime_type' => $object['mime_type'] ?? $file->mime_type,
            'upload_status' => UploadStatus::COMPLETED,
            'updated_by' => Auth::id(),
        ]);
        $this->saveUnique(fn (): bool => $file->save());
        $this->auditLogger->record(self::AUDIT_TYPE, $file->id, AuditEvent::CREATED, null, $this->auditValues($file));

        return $file;
    }

    /**
     * @return array{url: string, expires_at: string}
     */
    public function downloadUrl(int $id): array
    {
        $file = $this->liveFile($id);
        if ($file->upload_status !== UploadStatus::COMPLETED) {
            throw ValidationException::withMessages(['id' => Messages::E0034]);
        }

        return [
            'url' => $this->storage->presignedGetUrl((string) $file->minio_object_key, $file->original_name, MediaConst::DOWNLOAD_URL_TTL_SECONDS),
            'expires_at' => now()->addSeconds(MediaConst::DOWNLOAD_URL_TTL_SECONDS)->toIso8601String(),
        ];
    }

    /**
     * Rename and/or move a file or a folder; a folder takes its whole subtree along.
     *
     * @param  array{name?: string|null, folder?: string|null}  $payload
     */
    public function update(int $id, array $payload): int
    {
        $item = $this->media->findLive($id) ?? throw (new ModelNotFoundException)->setModel(MediaMgmt::class, [$id]);
        $oldPath = $item->virtual_path;
        $name = $payload['name'] ?? $item->original_name;
        $folder = $payload['folder'] ?? $this->parentOf($oldPath);

        if (! $item->is_file && ($folder === $oldPath || str_starts_with($folder, $oldPath.MediaConst::PATH_SEPARATOR))) {
            throw ValidationException::withMessages(['folder' => Messages::E0033]);
        }
        $newPath = $this->freePath($folder, $name, $item->id);
        if ($newPath === $oldPath && $name === $item->original_name) {
            return $item->id;
        }

        $old = $this->auditValues($item);
        $item->fill(['virtual_path' => $newPath, 'original_name' => $name, 'updated_by' => Auth::id()]);
        $this->saveUnique(fn (): bool => $item->save());
        if (! $item->is_file) {
            $this->media->moveDescendants($oldPath, $newPath);
        }
        $this->auditLogger->record(self::AUDIT_TYPE, $item->id, AuditEvent::UPDATED, $old, $this->auditValues($item));

        return $item->id;
    }

    /**
     * Soft-deletes the rows (a folder with everything below it), then removes the objects once the
     * transaction has committed. The bucket keeps old versions 30 days, so a mistake can be undone.
     *
     * @param  array{ids: list<int>}  $payload
     */
    public function delete(array $payload): void
    {
        $ids = [];
        $keys = [];
        foreach ($payload['ids'] as $id) {
            $item = $this->media->findLive($id);
            if ($item === null) {
                continue;
            }
            $items = $item->is_file ? [$item] : [$item, ...$this->media->descendants($item->virtual_path)];
            foreach ($items as $row) {
                $ids[] = $row->id;
                if ($row->is_file && $row->minio_object_key !== null) {
                    $keys[] = $row->minio_object_key;
                }
            }
            $this->auditLogger->record(self::AUDIT_TYPE, $item->id, AuditEvent::DELETED, $this->auditValues($item));
        }

        $this->media->markDeleted(array_values(array_unique($ids)), Auth::id());
        DB::afterCommit(function () use ($keys): void {
            foreach ($keys as $key) {
                $this->storage->delete($key);
            }
        });
    }

    private function liveFile(int $id): MediaMgmt
    {
        $file = $this->media->findLive($id);
        if ($file === null || ! $file->is_file) {
            throw (new ModelNotFoundException)->setModel(MediaMgmt::class, [$id]);
        }

        return $file;
    }

    private function existingFolder(string $folder): string
    {
        if (! $this->media->folderExists($folder)) {
            throw ValidationException::withMessages(['folder' => Messages::E0028]);
        }

        return $folder;
    }

    /** Path of $name inside $folder, refused when the folder is missing or the name is taken. */
    private function freePath(string $folder, string $name, ?int $exceptId = null): string
    {
        $folder = $this->existingFolder($folder);
        $path = ($folder === MediaConst::ROOT ? '' : $folder).MediaConst::PATH_SEPARATOR.$name;
        if ($this->media->pathTaken($path, $exceptId)) {
            throw ValidationException::withMessages(['name' => Messages::E0029]);
        }

        return $path;
    }

    private function parentOf(string $path): string
    {
        $parent = substr($path, 0, (int) strrpos($path, MediaConst::PATH_SEPARATOR));

        return $parent === '' ? MediaConst::ROOT : $parent;
    }

    /**
     * Two requests can pass pathTaken() together; the unique index decides, and the loser gets a 422.
     *
     * @template T
     *
     * @param  callable(): T  $save
     * @return T
     */
    private function saveUnique(callable $save): mixed
    {
        try {
            return $save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => Messages::E0029]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(MediaMgmt $item): array
    {
        return array_filter([
            'path' => $item->virtual_path,
            'is_file' => $item->is_file,
            'size' => $item->size,
        ], fn (mixed $value): bool => $value !== null);
    }
}
