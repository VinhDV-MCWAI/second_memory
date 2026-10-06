<?php

namespace App\Console\Commands\Media;

use App\Enums\UploadStatus;
use App\Jobs\Media\ProcessLargeFile;
use App\Models\Management\MediaMgmt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Retry stuck uploads that are in PROCESSING state
 * Useful for recovering from queue worker downtime
 */
class RetryStuckUploadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:retry-stuck-uploads {--hours=1 : Files stuck for more than N hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry stuck uploads that are in PROCESSING state';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $this->info("Finding uploads stuck in PROCESSING for more than {$hours} hour(s)...");

        $stuckUploads = MediaMgmt::where('upload_status', UploadStatus::PROCESSING->value)
            ->where('updated_at', '<', now()->subHours($hours))
            ->get();

        if ($stuckUploads->isEmpty()) {
            $this->info('No stuck uploads found.');

            return 0;
        }

        $this->info("Found {$stuckUploads->count()} stuck upload(s). Retrying...");

        $successCount = 0;
        $failedCount = 0;

        foreach ($stuckUploads as $media) {
            try {
                // Extract temp key from storage_path (assuming format: workspace/year/month/{uuid}.{ext})
                // The temp key should be: {uuid}.{ext}
                $pathParts = explode('/', $media->storage_path);
                $tempKey = end($pathParts);

                // Generate new room ID
                $uuid = Str::uuid()->toString();
                $userId = $media->created_by;
                $roomId = "{$uuid}_{$userId}_upload_file";

                // Dispatch job to retry
                ProcessLargeFile::dispatch($media, $tempKey, $roomId);

                $this->line("✓ Retrying upload for: {$media->original_name} (ID: {$media->id})");
                $successCount++;

                Log::info('Retrying stuck upload', [
                    'media_id' => $media->id,
                    'file' => $media->original_name,
                    'room_id' => $roomId,
                ]);

            } catch (\Exception $e) {
                $this->error("✗ Failed to retry: {$media->original_name} - {$e->getMessage()}");
                $failedCount++;

                Log::error('Failed to retry stuck upload', [
                    'media_id' => $media->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('Retry completed:');
        $this->info("- Success: {$successCount}");
        if ($failedCount > 0) {
            $this->error("- Failed: {$failedCount}");
        }

        return 0;
    }
}
