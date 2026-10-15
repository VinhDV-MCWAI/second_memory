<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constants\MediaConst;
use App\Services\Media\MultipartUploadService;
use Illuminate\Console\Command;

/**
 * Fails uploads left unfinished (browser closed mid-upload, job lost) and frees their names and space
 * (ADR-0014). Scheduled hourly in routes/console.php; MinIO also drops stale parts after 24 hours.
 */
final class CleanStuckUploadsCommand extends Command
{
    protected $signature = 'media:clean-stuck-uploads';

    protected $description = 'Abort uploads untouched for '.MediaConst::STUCK_UPLOAD_HOURS.' hours';

    public function handle(MultipartUploadService $uploads): int
    {
        $this->info('Cleaned '.$uploads->cleanStuck().' stuck upload(s).');

        return self::SUCCESS;
    }
}
