<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Master\AdminSessionService;
use Illuminate\Console\Command;

/**
 * Removes rows of the sessions screen (AUTH-04) whose session Redis has already dropped. Scheduled daily
 * in routes/console.php; the screen also drops such rows when it lists them.
 */
final class PruneAdminSessionsCommand extends Command
{
    protected $signature = 'auth:prune-sessions';

    protected $description = 'Remove rows of sessions unused for longer than a session lives';

    public function handle(AdminSessionService $sessions): int
    {
        $this->info('Removed '.$sessions->pruneExpired().' expired session row(s).');

        return self::SUCCESS;
    }
}
