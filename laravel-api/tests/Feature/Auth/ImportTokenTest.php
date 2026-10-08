<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Constants\LedgerConst;
use App\Models\Audit\AuditLog;
use App\Models\Ledger\Evidence;
use App\Models\Master\AdminMst;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * API token of the importer CLI (ADR-0010): minted by `ledger:import-token`, opens only the import route.
 */
final class ImportTokenTest extends TestCase
{
    private const IMPORT = '/api/admin/evidence/import';

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return ['notes' => [[
            'external_key' => 'lab/token.md',
            'title' => 'Imported with a token',
            'url' => 'https://notes.example.com/lab/token',
            'occurred_on' => '2026-10-01',
        ]]];
    }

    /**
     * @param  list<string>  $abilities
     */
    private function tokenFor(AdminMst $admin, array $abilities = [LedgerConst::IMPORT_ABILITY], ?Carbon $expiresAt = null): string
    {
        return $admin->createToken(LedgerConst::IMPORT_TOKEN_NAME, $abilities, $expiresAt ?? Carbon::now()->addDay())->plainTextToken;
    }

    public function test_an_owner_token_with_the_ability_imports_and_is_the_audit_actor(): void
    {
        $owner = AdminMst::factory()->owner()->create();

        $this->withToken($this->tokenFor($owner))->postJson(self::IMPORT, $this->payload())
            ->assertOk()
            ->assertJsonPath('data.created', 1);

        $evidence = Evidence::query()->sole();
        $this->assertSame($owner->id, AuditLog::query()->where(['auditable_type' => 'evidence', 'auditable_id' => $evidence->id])->sole()->admin_mst_id);
    }

    public function test_the_import_token_opens_no_other_admin_route(): void
    {
        $this->withToken($this->tokenFor(AdminMst::factory()->owner()->create()));

        $this->getJson('/api/admin/skill/list')->assertForbidden();
        $this->getJson('/api/admin/credential/me')->assertForbidden();
        $this->postJson('/api/admin/tag/store', ['name' => 'leaked'])->assertForbidden();
        $this->assertDatabaseMissing('tag', ['name' => 'leaked']);
    }

    public function test_a_token_without_the_ability_is_forbidden(): void
    {
        $this->withToken($this->tokenFor(AdminMst::factory()->owner()->create(), ['something:else']))
            ->postJson(self::IMPORT, $this->payload())
            ->assertForbidden();
    }

    public function test_a_viewers_token_cannot_write(): void
    {
        $this->withToken($this->tokenFor(AdminMst::factory()->create()))
            ->postJson(self::IMPORT, $this->payload())
            ->assertForbidden();
        $this->assertSame(0, Evidence::query()->count());
    }

    public function test_an_expired_token_or_a_disabled_admins_token_is_unauthenticated(): void
    {
        $owner = AdminMst::factory()->owner()->create();
        $this->withToken($this->tokenFor($owner, expiresAt: Carbon::now()->subMinute()))
            ->postJson(self::IMPORT, $this->payload())
            ->assertUnauthorized();

        $disabled = AdminMst::factory()->owner()->create();
        $token = $this->tokenFor($disabled);
        $disabled->update(['is_active' => false]);
        $this->withToken($token)->postJson(self::IMPORT, $this->payload())->assertUnauthorized();

        $deleted = AdminMst::factory()->owner()->create();
        $token = $this->tokenFor($deleted);
        $deleted->update(['is_delete' => true]);
        $this->withToken($token)->postJson(self::IMPORT, $this->payload())->assertUnauthorized();
    }

    public function test_the_route_is_throttled(): void
    {
        $this->withToken($this->tokenFor(AdminMst::factory()->owner()->create()));
        for ($i = 0; $i < LedgerConst::IMPORT_RATE_PER_MINUTE; $i++) {
            $this->postJson(self::IMPORT, ['notes' => [], 'dry_run' => true])->assertOk();
        }

        $this->postJson(self::IMPORT, ['notes' => [], 'dry_run' => true])->assertTooManyRequests();
    }

    public function test_the_command_mints_a_hashed_token_for_an_active_owner_only(): void
    {
        $owner = AdminMst::factory()->owner()->create();

        $this->artisan('ledger:import-token', ['login_id' => $owner->user_name, '--days' => 30])->assertSuccessful();

        $token = PersonalAccessToken::query()->sole();
        $this->assertSame($owner->id, $token->tokenable_id);
        $this->assertSame([LedgerConst::IMPORT_ABILITY], $token->abilities);
        $this->assertSame(Carbon::now()->addDays(30)->toDateString(), $token->expires_at?->toDateString());
        $this->assertSame(64, strlen($token->token));

        $this->artisan('ledger:import-token', ['login_id' => AdminMst::factory()->create()->user_name])->assertFailed();
        $this->artisan('ledger:import-token', ['login_id' => 'nobody'])->assertFailed();
        $this->artisan('ledger:import-token', ['login_id' => $owner->user_name, '--days' => '0'])->assertExitCode(2);
        $this->assertSame(1, PersonalAccessToken::query()->count());
    }

    public function test_the_printed_token_authenticates_until_it_is_revoked(): void
    {
        $owner = AdminMst::factory()->owner()->create();
        $owner->createToken('other', []);

        $this->assertSame(0, Artisan::call('ledger:import-token', ['login_id' => $owner->user_name]));
        $this->assertSame(1, preg_match('/^(\d+\|\w{40,})$/m', Artisan::output(), $match));
        $this->withToken($match[1])->postJson(self::IMPORT, ['notes' => [], 'dry_run' => true])->assertOk();

        $this->artisan('ledger:import-token', ['login_id' => $owner->user_name, '--revoke' => true])
            ->expectsOutput('Revoked 1 importer token(s).')
            ->assertSuccessful();

        $this->withToken($match[1])->postJson(self::IMPORT, ['notes' => [], 'dry_run' => true])->assertUnauthorized();
        // Only the importer tokens go
        $this->assertSame(['other'], PersonalAccessToken::query()->pluck('name')->all());
    }
}
