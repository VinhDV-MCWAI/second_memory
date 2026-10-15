<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Permissions\AdminRoutePermissions;
use Illuminate\Console\Command;

/**
 * Registers every named admin route as a permission (ADR-0013). Idempotent; stale permissions
 * are only reported unless --prune is given, because deleting one also removes it from every role.
 */
final class SyncRoutePermissionsCommand extends Command
{
    protected $signature = 'permission:sync-routes {--prune : Delete permissions whose route no longer exists}';

    protected $description = 'Create a permission for every named admin API route';

    public function handle(AdminRoutePermissions $permissions): int
    {
        $unnamed = $permissions->unnamed();
        if ($unnamed !== []) {
            $this->error('Admin routes without a name (every admin route needs one): '.implode(', ', $unnamed));

            return self::FAILURE;
        }

        $result = $permissions->sync((bool) $this->option('prune'));

        $this->info('Added: '.($result['added'] === [] ? 'none' : implode(', ', $result['added'])));
        if ($result['stale'] !== []) {
            $verb = $this->option('prune') ? 'Deleted' : 'Stale (run with --prune to delete)';
            $this->warn($verb.': '.implode(', ', $result['stale']));
        }

        return self::SUCCESS;
    }
}
