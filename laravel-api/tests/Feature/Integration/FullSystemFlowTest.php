<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

class FullSystemFlowTest extends TestCase
{
    use AuthenticatesAdmins;
    use RefreshDatabase;

    public function test_scenario_3_session_revoked_on_logout()
    {
        $checkUrl = 'api/admin/admin-mst/list';

        // 1. Login
        $cookies = $this->loginAs(AdminMst::factory()->create());
        $this->assertArrayHasKey(config('session.cookie'), $cookies);

        // 2. Verify Access
        $this->call('GET', $checkUrl, [], $cookies)->assertStatus(CommonVal::HTTP_OK);

        // 3. Logout ends the server-side session
        $this->call('POST', '/api/admin/credential/logout', [], $cookies)->assertStatus(CommonVal::HTTP_OK);

        // 4. The old session cookie no longer works
        $this->call('GET', $checkUrl, [], $cookies)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }
}
