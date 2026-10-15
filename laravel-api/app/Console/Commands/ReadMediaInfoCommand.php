<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Media\MediaInfoService;
use Illuminate\Console\Command;

/**
 * Reads the duration and thumbnail of images, videos and audio uploaded before MEDIA-11. New uploads get
 * them from ReadMediaInfoJob; safe to run again (files already looked at are skipped).
 */
final class ReadMediaInfoCommand extends Command
{
    protected $signature = 'media:read-media-info';

    protected $description = 'Read the duration and thumbnail of uploaded media that were never read';

    public function handle(MediaInfoService $info): int
    {
        $this->info('Read '.$info->readMissing().' file(s).');

        return self::SUCCESS;
    }
}
