<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Constants\MediaConst;
use App\Constants\Messages;
use App\Enums\AuditEvent;
use App\Enums\PreviewKind;
use App\Enums\UploadStatus;
use App\Jobs\Media\ReadMediaInfoJob;
use App\Models\Media\MediaMgmt;
use App\Repositories\Media\MediaRepository;
use App\Services\AuditLogger;
use Aws\S3\Exception\S3Exception;
use ErrorException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ValueError;

/**
 * File manager (ADR-0014): the browser sends bytes straight to MinIO through presigned URLs;
 * this service checks the inputs, hands out the URLs and keeps `media_mgmt` in step.
 */
final class MediaService
{
    public const AUDIT_TYPE = 'media';

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

        $folder = $this->media->create([
            'is_file' => false,
            'virtual_path' => $path,
            'original_name' => $payload['name'],
            'upload_status' => UploadStatus::COMPLETED,
            'created_by' => Auth::id(),
        ]);
        $this->auditLogger->record(self::AUDIT_TYPE, $folder->id, AuditEvent::CREATED, null, $this->auditValues($folder));

        return $folder->id;
    }

    /** Creates the folder and any missing parent, like `mkdir -p`; each new folder is audited. */
    public function ensureFolder(string $path): void
    {
        if ($path === MediaConst::ROOT || $this->media->folderExists($path)) {
            return;
        }
        $parent = $this->parentOf($path);
        $this->ensureFolder($parent);
        $this->storeFolder(['folder' => $parent, 'name' => substr($path, strrpos($path, MediaConst::PATH_SEPARATOR) + 1)]);
    }

    public function pathTaken(string $path): bool
    {
        return $this->media->pathTaken($path);
    }

    /**
     * Creates the row in "uploading" state and a presigned PUT URL for the browser.
     *
     * @param  array{folder?: string|null, name: string, size: int, mime_type: string}  $payload
     * @return array{id: int, url: string, method: string, headers: array{'Content-Type': string}, expires_at: string}
     */
    public function createUpload(array $payload): array
    {
        $file = $this->reserveFile($payload);
        $key = (string) $file->minio_object_key;

        return [
            'id' => $file->id,
            'url' => $this->storage->presignedPutUrl($key, $payload['mime_type'], MediaConst::UPLOAD_URL_TTL_SECONDS),
            'method' => 'PUT',
            'headers' => ['Content-Type' => $payload['mime_type']],
            'expires_at' => now()->addSeconds(MediaConst::UPLOAD_URL_TTL_SECONDS)->toIso8601String(),
        ];
    }

    /**
     * The "uploading" row of a new file, with its object key (`year/month/uuid.ext`). It holds no name
     * yet: the name is checked again when the upload finishes.
     *
     * @param  array{folder?: string|null, name: string, size: int, mime_type: string}  $payload
     */
    public function reserveFile(array $payload): MediaMgmt
    {
        $path = $this->freePath($payload['folder'] ?? MediaConst::ROOT, $payload['name']);
        $extension = Str::lower(pathinfo($payload['name'], PATHINFO_EXTENSION));

        return $this->media->create([
            'is_file' => true,
            'virtual_path' => $path,
            'original_name' => $payload['name'],
            'extension' => $extension === '' ? null : $extension,
            'mime_type' => $payload['mime_type'],
            'size' => $payload['size'],
            'minio_bucket' => $this->storage->bucket(),
            'minio_object_key' => now()->format('Y/m').'/'.Str::uuid()->toString().($extension === '' ? '' : '.'.$extension),
            'upload_status' => UploadStatus::UPLOADING,
            'created_by' => Auth::id(),
        ]);
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

        $this->markCompleted($file, $object, Auth::id());

        return $file;
    }

    /**
     * The object is in place and checked: the file becomes visible under its name.
     *
     * @param  array{size: int, etag: string, mime_type: string|null}  $object
     *
     * @throws ValidationException when another item took the name meanwhile
     */
    public function markCompleted(MediaMgmt $file, array $object, ?int $actorId): void
    {
        $dimensions = $this->imageSize($file);
        $file->fill([
            'width' => $dimensions['width'] ?? null,
            'height' => $dimensions['height'] ?? null,
            'minio_etag' => $object['etag'],
            'mime_type' => $object['mime_type'] ?? $file->mime_type,
            'upload_id' => null,
            'upload_status' => UploadStatus::COMPLETED,
            'updated_by' => $actorId,
        ]);
        $this->media->save($file);
        $this->auditLogger->record(self::AUDIT_TYPE, $file->id, AuditEvent::CREATED, null, $this->auditValues($file), $actorId);
        if (MediaInfoService::wanted($file)) {
            ReadMediaInfoJob::dispatch($file->id)->afterCommit();
        }
    }

    /**
     * @return array{url: string, expires_at: string}
     */
    public function downloadUrl(int $id, bool $inline = false): array
    {
        $file = $this->liveFile($id);
        if ($file->upload_status !== UploadStatus::COMPLETED) {
            throw ValidationException::withMessages(['id' => Messages::E0034]);
        }
        if ($inline && PreviewKind::forFile($file->extension, $file->size) === null) {
            throw ValidationException::withMessages(['inline' => Messages::E0039]);
        }
        $ttl = $inline ? MediaConst::PREVIEW_URL_TTL_SECONDS : MediaConst::DOWNLOAD_URL_TTL_SECONDS;
        $contentType = $inline ? PreviewKind::contentType($file->extension) : null;

        return [
            'url' => $this->storage->presignedGetUrl((string) $file->minio_object_key, $file->original_name, $ttl, $contentType),
            'expires_at' => now()->addSeconds($ttl)->toIso8601String(),
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
        $this->media->save($item);
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
        $rows = [];
        foreach ($payload['ids'] as $id) {
            $item = $this->media->findLive($id);
            if ($item === null) {
                continue;
            }
            foreach ($item->is_file ? [$item] : [$item, ...$this->media->descendants($item->virtual_path)] as $row) {
                $rows[$row->id] = $row;
            }
            $this->auditLogger->record(self::AUDIT_TYPE, $item->id, AuditEvent::DELETED, $this->auditValues($item));
        }

        $this->media->markDeleted(array_keys($rows), Auth::id());
        DB::afterCommit(function () use ($rows): void {
            foreach ($rows as $row) {
                $this->discardObject($row);
            }
        });
    }

    /**
     * Removes what MinIO holds for a file row: the parts of an unfinished multipart upload, or the
     * object. Folders hold nothing.
     */
    public function discardObject(MediaMgmt $file): void
    {
        if (! $file->is_file || $file->minio_object_key === null) {
            return;
        }
        if ($file->upload_id !== null) {
            $this->storage->abortMultipartUpload($file->minio_object_key, $file->upload_id);
        } else {
            $this->storage->delete($file->minio_object_key);
        }
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

    public function parentOf(string $path): string
    {
        $parent = substr($path, 0, (int) strrpos($path, MediaConst::PATH_SEPARATOR));

        return $parent === '' ? MediaConst::ROOT : $parent;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Width and height of an image file, read from the start of its object (MEDIA-10). Null for other
     * files and for an image whose header cannot be read: the upload itself still succeeds.
     *
     * @return array{width: int, height: int}|null
     */
    public function imageSize(MediaMgmt $file): ?array
    {
        if (PreviewKind::forFile($file->extension, $file->size) !== PreviewKind::IMAGE || $file->minio_object_key === null) {
            return null;
        }
        try {
            $info = getimagesizefromstring($this->storage->readStart($file->minio_object_key, MediaConst::IMAGE_PROBE_BYTES));
        } catch (S3Exception|ErrorException|ValueError) {
            return null;
        }

        return $info === false || $info[0] < 1 || $info[1] < 1 ? null : ['width' => $info[0], 'height' => $info[1]];
    }

    /**
     * Reads the size of completed images that have none yet (uploaded before MEDIA-10). The rows keep
     * their updated_at: nobody changed the file.
     *
     * @return array{read: int, unreadable: int}
     */
    public function fillMissingImageSizes(): array
    {
        $counts = ['read' => 0, 'unreadable' => 0];
        foreach ($this->media->imagesWithoutSize(array_keys(array_filter(
            MediaConst::PREVIEW_CONTENT_TYPES,
            fn (string $type): bool => str_starts_with($type, 'image/'),
        ))) as $file) {
            $dimensions = $this->imageSize($file);
            if ($dimensions === null) {
                $counts['unreadable']++;

                continue;
            }
            $this->media->setImageSize($file->id, $dimensions['width'], $dimensions['height']);
            $counts['read']++;
        }

        return $counts;
    }

    public function auditValues(MediaMgmt $item): array
    {
        return array_filter([
            'path' => $item->virtual_path,
            'is_file' => $item->is_file,
            'size' => $item->size,
        ], fn (mixed $value): bool => $value !== null);
    }
}
