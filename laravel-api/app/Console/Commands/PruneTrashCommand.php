<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constants\MediaConst;
use App\Services\Media\MediaTrashService;
use Illuminate\Console\Command;

/**
 * Deletes the rows of files and folders deleted more than TRASH_DAYS ago (MEDIA-08): MinIO has dropped
 * their bytes, so they could never be restored. Scheduled daily in routes/console.php.
 */
final class PruneTrashCommand extends Command
{
    protected $signature = 'media:prune-trash';

    protected $description = 'Remove rows deleted more than '.MediaConst::TRASH_DAYS.' days ago';

    public function handle(MediaTrashService $trash): int
    {
        $this->info('Removed '.$trash->pruneExpired().' expired row(s).');

        return self::SUCCESS;
    }
}
