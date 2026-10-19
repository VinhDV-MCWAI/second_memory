<?php

declare(strict_types=1);

namespace Tests\Feature\Master;

use App\Constants\CommonVal;
use App\Models\Audit\AuditLog;
use App\Models\Master\AdminMst;
use App\Models\User\EndUser;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\AuthenticatesEndUsers;
use Tests\TestCase;

/**
 * End users in the admin screens (ADR-0020).
 */
final class EndUserApiTest extends TestCase
{
    use AuthenticatesAdmins, AuthenticatesEndUsers;

    private const BASE = '/api/admin/end-user';

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_list_lock_reset_password_and_delete_with_audit(): void
    {
        $endUser = EndUser::factory()->create(['username' => 'dan', 'display_name' => 'Dan']);
        $userCookies = $this->loginAsEndUser($endUser);

        $this->call('GET', self::BASE.'/list', ['username' => 'DA'], $this->owner)->assertOk()
            ->assertJsonPath('data.data.0.username', 'dan')
            ->assertJsonPath('data.data.0.is_active', true)
            ->assertJsonPath('data.data.0.saved_count', 0)
            ->assertJsonMissingPath('data.data.0.password');

        $this->call('PUT', self::BASE."/update/{$endUser->id}", ['is_active' => 0, 'password' => 'reset1234'], $this->owner)->assertOk();
        $this->call('GET', '/api/user/me', [], $userCookies)->assertUnauthorized();
        $updated = AuditLog::query()->where(['auditable_type' => 'end_user', 'auditable_id' => $endUser->id])->latest('id')->firstOrFail();
        $this->assertSame(['is_active' => false], $updated->new_values);

        $this->call('PUT', self::BASE."/update/{$endUser->id}", ['is_active' => 1], $this->owner)->assertOk();
        $this->loginAsEndUser($endUser, 'reset1234');

        $this->call('POST', self::BASE.'/delete', ['ids' => [$endUser->id]], $this->owner)->assertOk();
        $this->assertDatabaseMissing('end_user', ['id' => $endUser->id]);
    }

    public function test_the_registration_switch_is_audited(): void
    {
        $this->call('GET', self::BASE.'/registration', [], $this->owner)->assertJsonPath('data.open', true);
        $this->call('PUT', self::BASE.'/registration-update', ['open' => false], $this->owner)->assertOk();

        $this->call('GET', self::BASE.'/registration', [], $this->owner)->assertJsonPath('data.open', false);
        $this->call('GET', '/api/user/registration')->assertJsonPath('data.open', false);
        $audit = AuditLog::query()->where('auditable_type', 'app_setting')->latest('id')->firstOrFail();
        // Only one setting exists so far, so the row keeps just the value
        $this->assertSame(['value' => true], $audit->old_values);
        $this->assertSame(['value' => false], $audit->new_values);
    }

    public function test_the_viewer_reads_but_cannot_change(): void
    {
        $viewer = $this->loginAs(AdminMst::factory()->create());
        $endUser = EndUser::factory()->create();

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk();
        $this->call('GET', self::BASE.'/registration', [], $viewer)->assertOk();
        $this->call('PUT', self::BASE."/update/{$endUser->id}", ['is_active' => 0], $viewer)->assertForbidden();
        $this->call('PUT', self::BASE.'/registration-update', ['open' => false], $viewer)->assertForbidden();
    }

    public function test_update_checks_the_fields(): void
    {
        $endUser = EndUser::factory()->create();
        $this->call('PUT', self::BASE."/update/{$endUser->id}", ['is_active' => 3, 'password' => 'short'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['is_active', 'password']]]);
    }
}
