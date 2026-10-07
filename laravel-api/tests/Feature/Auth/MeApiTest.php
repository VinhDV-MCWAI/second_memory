<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

final class MeApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const string ME_URL = '/api/admin/credential/me';

    private const string LIST_URL = 'api/admin/admin-mst/list';

    private const string DELETE_URL = 'api/admin/admin-mst/delete';

    public function test_me_without_a_session_is_401(): void
    {
        $this->getJson(self::ME_URL)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_me_returns_the_signed_in_admin_without_route_permissions(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginAs($admin);

        $this->call('GET', self::ME_URL, [], $cookies)
            ->assertOk()
            ->assertJsonPath('data.id', $admin->id)
            ->assertJsonPath('data.email', $admin->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_a_deleted_admin_loses_the_session_on_the_next_request(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginAs($admin);

        $admin->update(['is_delete' => true]);

        // Was AUTH-GUIDE A13 / T019: a deleted admin kept getting new tokens
        $this->call('GET', self::ME_URL, [], $cookies)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_a_disabled_admin_loses_the_session_on_the_next_request(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginAs($admin);

        $admin->update(['is_active' => false]);

        $this->call('GET', self::ME_URL, [], $cookies)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_protected_route_is_401_without_a_session(): void
    {
        $this->getJson(self::LIST_URL)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_a_viewer_may_read_but_not_write(): void
    {
        $cookies = $this->loginAs(AdminMst::factory()->create());
        $other = AdminMst::factory()->create();

        $this->call('GET', self::LIST_URL, [], $cookies)->assertOk();
        $this->call('POST', self::DELETE_URL, ['ids' => [$other->id]], $cookies)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    public function test_a_role_change_applies_without_logging_in_again(): void
    {
        AdminMst::factory()->owner()->create();
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginAs($admin);
        $other = AdminMst::factory()->create();
        $this->call('POST', self::DELETE_URL, ['ids' => [$other->id]], $cookies)->assertStatus(CommonVal::HTTP_FORBIDDEN);

        $admin->update(['role' => AdminRole::OWNER]);

        // The role is read from the admin row on every request (ADR-0005)
        $this->call('POST', self::DELETE_URL, ['ids' => [$other->id]], $cookies)->assertOk();
    }
}
