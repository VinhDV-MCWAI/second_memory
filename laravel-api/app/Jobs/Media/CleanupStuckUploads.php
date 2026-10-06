<?php

namespace App\Jobs\Media;

use App\Enums\UploadStatus;
use App\Models\Management\MediaMgmt;
use App\Services\MinioService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupStuckUploads implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     *
     * Finds and cleans up media files stuck in PROCESSING status for more than 24 hours
     */
    public function handle(MinioService $minioService): void
    {
        Log::info('[CleanupStuckUploads] Starting cleanup of stuck uploads');

        try {
            // Find all media files stuck in PROCESSING for more than 24 hours
            $stuckUploadIds = MediaMgmt::where('upload_status', UploadStatus::PROCESSING->value)
                ->where('updated_at', '<', Carbon::now()->subHours(24))
                ->pluck('id')
                ->toArray();

            if (empty($stuckUploadIds)) {
                Log::info('[CleanupStuckUploads] No stuck uploads found');

                return;
            }

            $cleanedCount = 0;
            $failedCount = 0;

            // Refetch fresh instances for each ID to avoid serialization issues
            foreach ($stuckUploadIds as $mediaId) {
                $media = MediaMgmt::find($mediaId);
                if (! $media) {
                    continue;
                }
                try {
                    // Mark as failed in database
                    $media->upload_status = UploadStatus::FAILED;
                    $media->save();

                    // Optionally: Clean up incomplete file in official bucket if exists
                    if ($media->minio_bucket && $media->minio_object_key) {
                        if ($minioService->exists($media->minio_bucket, $media->minio_object_key)) {
                            $minioService->delete($media->minio_bucket, $media->minio_object_key);
                            Log::info('[CleanupStuckUploads] Deleted incomplete file', [
                                'media_id' => $media->id,
                                'bucket' => $media->minio_bucket,
                                'key' => $media->minio_object_key,
                            ]);
                        }
                    }

                    // Optionally: Soft delete the record to remove from listings
                    $media->is_delete = true;
                    $media->save();

                    $cleanedCount++;

                    Log::info('[CleanupStuckUploads] Cleaned stuck upload', [
                        'media_id' => $media->id,
                        'original_name' => $media->original_name,
                        'stuck_since' => $media->updated_at->diffForHumans(),
                    ]);
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('[CleanupStuckUploads] Failed to clean upload', [
                        'media_id' => $media->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('[CleanupStuckUploads] Cleanup job failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
