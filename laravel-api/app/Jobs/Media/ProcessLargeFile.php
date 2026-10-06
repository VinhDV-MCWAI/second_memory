<?php

namespace App\Jobs\Media;

use App\Constants\MediaConst;
use App\Models\Management\MediaMgmt;
use App\Services\MinioService;
use App\Services\WebSocket\RedisPublisher;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLargeFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [1, 5, 10, 20, 30];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected MediaMgmt $media,
        protected string $tempKey,
        protected string $roomId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MinioService $minioService, RedisPublisher $redisPublisher): void
    {
        $startTime = microtime(true);
        Log::info("Processing large file upload for Media ID: {$this->media->id}, Room ID: {$this->roomId}");

        try {
            // 1. Move file from Temp to Official
            $sourceDisk = MediaConst::DISK_TEMP;
            $destDisk = $this->media->minio_bucket;
            $destKey = $this->media->minio_object_key;
            $fileSize = $this->media->size;

            Log::info('Moving file', [
                'from' => "{$sourceDisk}:{$this->tempKey}",
                'to' => "{$destDisk}:{$destKey}",
                'size' => $fileSize,
            ]);

            // Pass file size for optimized copy
            if (! $minioService->move($sourceDisk, $this->tempKey, $destDisk, $destKey, $fileSize)) {
                throw new Exception('Minio move failed');
            }

            // 2. Update DB status to completed
            $this->media->upload_status = \App\Enums\UploadStatus::COMPLETED;
            $this->media->save();

            $duration = microtime(true) - $startTime;

            // 3. Notify User via Reverb (Event)
            $userId = $this->media->created_by;

            broadcast(new \App\Events\UploadStatusUpdated(
                userId: $userId,
                roomId: $this->roomId,
                status: \App\Enums\UploadStatus::COMPLETED->value,
                message: 'Upload completed successfully.',
                fileId: $this->media->id,
                url: $this->media->url
            ));

            Log::info('ProcessLargeFile completed', [
                'media_id' => $this->media->id,
                'duration' => round($duration, 2).'s',
                'throughput' => round($fileSize / $duration / 1024 / 1024, 2).' MB/s',
            ]);
        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;

            Log::error('ProcessLargeFile failed', [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'duration' => round($duration, 2).'s',
            ]);

            // If it's the last attempt, mark as failed
            if ($this->attempts() >= $this->tries) {
                $this->media->upload_status = \App\Enums\UploadStatus::FAILED;
                $this->media->save();

                $userId = $this->media->created_by;

                broadcast(new \App\Events\UploadStatusUpdated(
                    userId: $userId,
                    roomId: $this->roomId,
                    status: \App\Enums\UploadStatus::FAILED->value,
                    message: 'Upload failed after retries.'
                ));
            }

            throw $e; // Trigger retry
        }
    }
}
