<?php

declare(strict_types=1);

namespace App\Jobs\Media;

use App\Models\Media\MediaMgmt;
use App\Services\Media\MediaInfoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Duration and thumbnail of a completed upload (MEDIA-11). Silent: nobody waits for it, so it is not a
 * BackgroundJob (no on-screen result); the Files listing shows them once they are there.
 */
final class ReadMediaInfoJob implements ShouldQueue
{
    use Queueable;

    /** A file ffmpeg cannot read fails the same way again */
    public int $tries = 1;

    /** Two ffmpeg runs of FFMPEG_TIMEOUT_SECONDS each, plus the upload of the thumbnail */
    public int $timeout = 180;

    public function __construct(public readonly int $mediaId) {}

    public function handle(MediaInfoService $info): void
    {
        $file = MediaMgmt::query()->live()->find($this->mediaId);
        if ($file !== null) {
            $info->read($file);
        }
    }
}
