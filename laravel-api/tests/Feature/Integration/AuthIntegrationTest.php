<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Roles end to end (ADR-0005): an owner creates an admin, who starts as a read-only viewer.
 */
class AuthIntegrationTest extends TestCase
{
    use AuthenticatesAdmins;
    use RefreshDatabase;

    public function test_owner_creates_an_admin_who_is_a_viewer_by_default()
    {
        $ownerCookies = $this->loginAsOwner(AdminMst::factory()->create());

        $this->call('POST', 'api/admin/admin-mst/store', [
            'email' => 'new.viewer@gmail.com',
            'user_name' => 'new_viewer',
            'password' => 'password',
            'first_name' => 'New',
            'last_name' => 'Viewer',
            'gender' => 1,
            'status' => 1,
            'is_active' => 1,
            'is_delete' => 0,
        ], $ownerCookies)->assertStatus(200);

        $viewer = AdminMst::where('user_name', 'new_viewer')->firstOrFail();
        $this->assertSame(AdminRole::VIEWER, $viewer->role);

        $viewerCookies = $this->loginAs($viewer);
        $this->call('GET', 'api/admin/admin-mst/list', [], $viewerCookies)->assertStatus(200);
        $this->call('POST', 'api/admin/admin-mst/delete', ['ids' => [$viewer->id]], $viewerCookies)->assertStatus(403);
    }

    public function test_login_failure_invalid_credentials()
    {
        $admin = AdminMst::factory()->create();

        $this->postJson('/api/admin/credential/login', [
            'user_name' => $admin->user_name,
            'password' => 'wrong_password',
        ])->assertStatus(401);
    }
}
