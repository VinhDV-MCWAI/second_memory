<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class MeApiTest extends TestCase
{
    use GrantsApiAccess;

    private const string ME_URL = '/api/admin/credential/me';

    private const string PROTECTED_URL = 'api/admin/token-mst/list';

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

    public function test_protected_route_is_401_without_session_and_403_without_permission(): void
    {
        $this->getJson(self::PROTECTED_URL)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);

        $cookies = $this->loginAs(AdminMst::factory()->create());

        $this->call('GET', self::PROTECTED_URL, [], $cookies)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    public function test_a_granted_permission_applies_without_logging_in_again(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, []);
        $this->call('GET', self::PROTECTED_URL, [], $cookies)->assertStatus(CommonVal::HTTP_FORBIDDEN);

        $this->loginWithAccess($admin, [['GET', self::PROTECTED_URL]]);

        // The permission is read per request (no cache to clear, AUTH-GUIDE A7)
        $this->call('GET', self::PROTECTED_URL, [], $cookies)->assertOk();
    }
}
