<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

final class LogoutApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const string LOGOUT_URL = '/api/admin/credential/logout';

    private const string ME_URL = '/api/admin/credential/me';

    public function test_logout_ends_the_session(): void
    {
        $cookies = $this->loginAs(AdminMst::factory()->create());

        $this->call('POST', self::LOGOUT_URL, [], $cookies)
            ->assertOk()
            ->assertJsonPath('error.status', false);

        $this->call('GET', self::ME_URL, [], $cookies)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_logout_without_a_session_still_succeeds(): void
    {
        // Idempotent (was AUTH-GUIDE A9): the SPA can always clear its state
        $this->postJson(self::LOGOUT_URL)->assertOk();
    }

    public function test_logout_keeps_other_sessions(): void
    {
        $admin = AdminMst::factory()->create();
        $firstDevice = $this->loginAs($admin);
        $secondDevice = $this->loginAs($admin);

        $this->call('POST', self::LOGOUT_URL, [], $firstDevice)->assertOk();

        $this->call('GET', self::ME_URL, [], $secondDevice)->assertOk();
    }

    public function test_logout_only_accepts_post(): void
    {
        $this->getJson(self::LOGOUT_URL)->assertStatus(CommonVal::HTTP_METHOD_NOT_ALLOWED);
    }
}
