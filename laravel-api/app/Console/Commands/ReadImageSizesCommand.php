<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Media\MediaService;
use Illuminate\Console\Command;

/**
 * Reads the width and height of images uploaded before MEDIA-10. New uploads get them when they
 * complete; safe to run again (only images without a size are read).
 */
final class ReadImageSizesCommand extends Command
{
    protected $signature = 'media:read-image-sizes';

    protected $description = 'Read the width and height of uploaded images that have none yet';

    public function handle(MediaService $media): int
    {
        $counts = $media->fillMissingImageSizes();
        $this->info("Read {$counts['read']} image size(s); {$counts['unreadable']} image(s) could not be read.");

        return self::SUCCESS;
    }
}
