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
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Trash of the file manager (MEDIA-07). Deleting a file leaves a delete marker on the versioned bucket;
 * MinIO keeps the bytes TRASH_DAYS days (docker/minio/ilm-history.json). Restoring drops the marker,
 * deleting for good removes every version. Only files are listed: restoring one recreates its folders.
 */
final class MediaTrashService
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly MediaRepository $media,
        private readonly ObjectStorage $storage,
        private readonly AuditLogger $auditLogger,
        private readonly FolderAccess $access,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return LengthAwarePaginator<int, MediaMgmt>
     */
    public function list(array $payload): LengthAwarePaginator
    {
        return $this->media->trash(self::keptSince(), $payload, $this->access->blockedFolders(true));
    }

    /**
     * Back to its folder (recreated when it was deleted too), renamed `name (restored).ext` when the
     * name was taken meanwhile.
     *
     * @throws ValidationException when MinIO no longer keeps the file
     */
    public function restore(int $id): MediaMgmt
    {
        $file = $this->media->inTrash(self::keptSince(), [$id], $this->access->blockedFolders(true))->first()
            ?? throw (new ModelNotFoundException)->setModel(MediaMgmt::class, [$id]);
        $key = (string) $file->minio_object_key;
        if (! $this->storage->restorable($key)) {
            throw ValidationException::withMessages(['id' => Messages::E0040]);
        }

        $folder = $this->mediaService->parentOf($file->virtual_path);
        $this->mediaService->ensureFolder($folder);
        $name = $this->freeName($folder, $file->original_name);
        $file->fill([
            'is_delete' => false,
            'original_name' => $name,
            'virtual_path' => self::pathIn($folder, $name),
            'updated_by' => Auth::id(),
        ]);
        $this->media->save($file);
        $this->auditLogger->record(MediaService::AUDIT_TYPE, $file->id, AuditEvent::RESTORED, null, $this->mediaService->auditValues($file));
        // After the commit: a rolled-back restore must leave the object deleted
        DB::afterCommit(fn () => $this->storage->restore($key));

        return $file;
    }

    /**
     * Delete for good: the rows go, every version in MinIO goes (after the commit).
     *
     * @param  list<int>|null  $ids  null empties the whole trash
     * @return int files deleted
     */
    public function purge(?array $ids): int
    {
        $rows = $this->media->inTrash(self::keptSince(), $ids, $this->access->blockedFolders(true));
        foreach ($rows as $file) {
            $this->auditLogger->record(MediaService::AUDIT_TYPE, $file->id, AuditEvent::PURGED, $this->mediaService->auditValues($file));
        }
        $this->media->forceDelete($rows);
        DB::afterCommit(fn () => $this->purgeObjects($rows));

        return $rows->count();
    }

    /**
     * Removes the rows of everything deleted more than TRASH_DAYS ago (MEDIA-08): files that left the
     * trash, folders, failed uploads. Their links to evidence go with them. Run daily by
     * `media:prune-trash`; MinIO has dropped the old versions already, purging again is a safety net.
     *
     * @return int rows removed
     */
    public function pruneExpired(): int
    {
        return DB::transaction(function (): int {
            $rows = $this->media->deletedBefore(self::keptSince());
            $files = $rows->filter(fn (MediaMgmt $row): bool => $row->is_file && $row->upload_status === UploadStatus::COMPLETED);
            foreach ($files as $file) {
                $this->auditLogger->record(MediaService::AUDIT_TYPE, $file->id, AuditEvent::PURGED, $this->mediaService->auditValues($file));
            }
            $this->media->forceDelete($rows);
            DB::afterCommit(fn () => $this->purgeObjects($files));

            return $rows->count();
        });
    }

    public static function keptSince(): CarbonInterface
    {
        return now()->subDays(MediaConst::TRASH_DAYS);
    }

    /** @param  Collection<int, MediaMgmt>  $rows */
    private function purgeObjects(Collection $rows): void
    {
        foreach ($rows as $file) {
            $this->storage->purge((string) $file->minio_object_key);
            $thumbnail = MediaInfoService::thumbnailKey($file);
            if ($thumbnail !== null) {
                $this->storage->purge($thumbnail);
            }
        }
    }

    private function freeName(string $folder, string $name): string
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $base = $extension === '' ? $name : substr($name, 0, -strlen($extension) - 1);
        $candidate = $name;
        for ($n = 1; $this->mediaService->pathTaken(self::pathIn($folder, $candidate)); $n++) {
            $suffix = MediaConst::RESTORED_SUFFIX.($n === 1 ? '' : " {$n}");
            $candidate = "{$base} ({$suffix})".($extension === '' ? '' : ".{$extension}");
        }

        return $candidate;
    }

    private static function pathIn(string $folder, string $name): string
    {
        return ($folder === MediaConst::ROOT ? '' : $folder).MediaConst::PATH_SEPARATOR.$name;
    }
}
