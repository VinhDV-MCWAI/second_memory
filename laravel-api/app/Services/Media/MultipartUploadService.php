<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Constants\MediaConst;
use App\Constants\Messages;
use App\Enums\UploadStatus;
use App\Exceptions\BackgroundTaskFailed;
use App\Jobs\Media\ProcessUploadJob;
use App\Models\Media\MediaMgmt;
use App\Repositories\Media\MediaRepository;
use App\Services\BackgroundTasks\BackgroundTaskTracker;
use Aws\S3\Exception\S3Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Large files (ADR-0014): the browser sends the parts straight to MinIO with one presigned URL each,
 * then reports them; joining and checking the file runs as a background job whose result reaches the
 * screen live. Uploads left unfinished are cleaned up by `media:clean-stuck-uploads`.
 *
 * @phpstan-import-type Task from BackgroundTaskTracker
 */
final class MultipartUploadService
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly MediaRepository $media,
        private readonly ObjectStorage $storage,
        private readonly BackgroundTaskTracker $tracker,
    ) {}

    /**
     * Part size for a file: the step for its size, and never so small that it needs more than MAX_PARTS.
     */
    public static function partSize(int $fileSize): int
    {
        $step = MediaConst::MIN_PART_BYTES;
        foreach (MediaConst::PART_SIZE_STEPS as $partSize => $upTo) {
            $step = $partSize;
            if ($fileSize <= $upTo) {
                break;
            }
        }

        return max($step, MediaConst::MIN_PART_BYTES, (int) ceil($fileSize / MediaConst::MAX_PARTS));
    }

    public static function partCount(int $fileSize): int
    {
        return max(1, (int) ceil($fileSize / self::partSize($fileSize)));
    }

    /**
     * @param  array{folder?: string|null, name: string, size: int, mime_type: string}  $payload
     * @return array{id: int, part_size: int, part_count: int}
     */
    public function start(array $payload): array
    {
        $file = $this->mediaService->reserveFile($payload);
        $file->upload_id = $this->storage->createMultipartUpload((string) $file->minio_object_key, $payload['mime_type']);
        $this->media->save($file);

        return ['id' => $file->id, 'part_size' => self::partSize($payload['size']), 'part_count' => self::partCount($payload['size'])];
    }

    /**
     * One presigned PUT URL per part number, asked for in batches as the browser gets to them.
     *
     * @param  list<int>  $partNumbers
     * @return array{parts: list<array{part_number: int, url: string}>, expires_at: string}
     */
    public function partUrls(int $id, array $partNumbers): array
    {
        $file = $this->ownUpload($id);
        $count = self::partCount((int) $file->size);
        $parts = [];
        foreach ($partNumbers as $number) {
            if ($number > $count) {
                throw ValidationException::withMessages(['part_numbers' => Messages::getMessage(Messages::E0015, ['attributes' => 'part_numbers', 'range' => "1-{$count}"])]);
            }
            $parts[] = [
                'part_number' => $number,
                'url' => $this->storage->presignedPartUrl((string) $file->minio_object_key, (string) $file->upload_id, $number, MediaConst::PART_URL_TTL_SECONDS),
            ];
        }

        return ['parts' => $parts, 'expires_at' => now()->addSeconds(MediaConst::PART_URL_TTL_SECONDS)->toIso8601String()];
    }

    /**
     * Every part is up: the file takes its name ("processing") and a background job joins and checks it.
     *
     * @param  list<array{part_number: int, etag: string}>  $parts
     * @return Task
     */
    public function complete(int $id, array $parts): array
    {
        $file = $this->ownUpload($id);
        $numbers = array_column($parts, 'part_number');
        sort($numbers);
        if ($numbers !== range(1, self::partCount((int) $file->size))) {
            throw ValidationException::withMessages(['parts' => Messages::E0037]);
        }

        $file->upload_status = UploadStatus::PROCESSING;
        $file->updated_by = Auth::id();
        $this->media->save($file);

        return $this->tracker->dispatch(new ProcessUploadJob((int) Auth::id(), $file->id, $parts));
    }

    /** The browser gave up (cancel button): drop the parts and the row. */
    public function abort(int $id): void
    {
        $file = $this->ownUpload($id);
        $this->fail($file);
    }

    /**
     * Run by ProcessUploadJob: join the parts, check the size, publish the file. Safe to run again:
     * a file whose parts were already joined is only checked.
     *
     * @param  list<array{part_number: int, etag: string}>  $parts
     *
     * @throws BackgroundTaskFailed when the parts cannot be joined or the size is wrong
     */
    public function process(int $id, array $parts, int $actorId): ?MediaMgmt
    {
        $file = $this->media->findLive($id);
        if ($file === null || $file->upload_status !== UploadStatus::PROCESSING) {
            return null; // Deleted or cleaned up meanwhile
        }
        $key = (string) $file->minio_object_key;

        if ($file->upload_id !== null) {
            try {
                $this->storage->completeMultipartUpload($key, $file->upload_id, $parts);
            } catch (S3Exception $e) {
                // 4xx: wrong or missing parts, retrying cannot fix it; 5xx: let the queue retry
                if ($e->getStatusCode() !== null && $e->getStatusCode() < 500) {
                    throw new BackgroundTaskFailed(Messages::E0038, 0, $e);
                }
                throw $e;
            }
            $file->upload_id = null;
            $this->media->save($file);
        }

        $object = $this->storage->head($key);
        if ($object === null || $object['size'] !== $file->size) {
            throw new BackgroundTaskFailed($object === null ? Messages::E0030 : Messages::E0031);
        }

        DB::transaction(fn () => $this->mediaService->markCompleted($file, $object, $actorId));

        return $file;
    }

    /** A file that will not finish: remove its parts or object, and hide the row (its name is free again). */
    public function fail(MediaMgmt $file): void
    {
        $this->mediaService->discardObject($file);
        $file->fill(['upload_status' => UploadStatus::FAILED, 'is_delete' => true, 'upload_id' => null]);
        $this->media->save($file);
    }

    /** Fails the uploads untouched for STUCK_UPLOAD_HOURS (browser closed, job lost). */
    public function cleanStuck(): int
    {
        $stuck = $this->media->stuckUploads(now()->subHours(MediaConst::STUCK_UPLOAD_HOURS));
        foreach ($stuck as $file) {
            $this->fail($file);
        }

        return $stuck->count();
    }

    /** An unfinished multipart upload started by the signed-in admin; anything else is a 404. */
    private function ownUpload(int $id): MediaMgmt
    {
        $file = $this->media->findLive($id);
        if ($file === null || $file->upload_id === null || $file->upload_status !== UploadStatus::UPLOADING || $file->created_by !== Auth::id()) {
            throw (new ModelNotFoundException)->setModel(MediaMgmt::class, [$id]);
        }

        return $file;
    }
}
