<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constants\LedgerConst;
use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Mints or revokes the API token of the Obsidian importer CLI (ADR-0010). Shell access only: no HTTP route
 * creates tokens. Sanctum stores the SHA-256 hash, so the plain token is shown once.
 */
final class ImportTokenCommand extends Command
{
    protected $signature = 'ledger:import-token
        {login_id : User name of an active owner}
        {--days='.LedgerConst::IMPORT_TOKEN_DAYS.' : Lifetime in days}
        {--revoke : Delete this admin\'s importer tokens instead}';

    protected $description = 'Create (or revoke) the API token used by the Obsidian importer';

    public function handle(): int
    {
        $admin = AdminMst::query()->notDeleted()->active()
            ->where('user_name', (string) $this->argument('login_id'))
            ->where('role', AdminRole::OWNER)
            ->first();
        if ($admin === null) {
            $this->error('No active owner with this login id.');

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            $revoked = PersonalAccessToken::query()->whereMorphedTo('tokenable', $admin)->where('name', LedgerConst::IMPORT_TOKEN_NAME)->delete();
            $this->info("Revoked {$revoked} importer token(s).");

            return self::SUCCESS;
        }

        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($days === false) {
            $this->error('--days must be a positive whole number.');

            return self::INVALID;
        }

        $expiresAt = Carbon::now()->addDays($days);
        $token = $admin->createToken(LedgerConst::IMPORT_TOKEN_NAME, [LedgerConst::IMPORT_ABILITY], $expiresAt);
        $this->line($token->plainTextToken);
        $this->info('Expires '.$expiresAt->toDateString().'. Store it as LEDGER_API_TOKEN; it is not shown again.');

        return self::SUCCESS;
    }
}
