<?php

namespace Tests\Feature\Master\AdminRoleMst;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

class UpdateAdminRoleMstTest extends TestCase
{
    use DatabaseTransactions;
    use GrantsApiAccess;

    protected string $updateUrl = '/api/admin/admin-role-mst/update';

    protected string $loginUrl = '/api/admin/credential/login';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    protected function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->firstOrCreate(
            ['name' => 'root'],
            ['permission' => '{}', 'is_active' => 1, 'is_delete' => 0]
        );
        $this->grantAccessTo($rootRole, 'PUT', 'api/admin/admin-role-mst/update');

        if (! DB::table('admin_role_mst')->where('admin_mst_id', $admin->id)->where('role_mst_id', $rootRole->id)->exists()) {
            DB::table('admin_role_mst')->insert([
                'admin_mst_id' => $admin->id,
                'role_mst_id' => $rootRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);

        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return $cookies;
    }

    public function test_ms_t_ar_m_up_d_001_unauthenticated()
    {
        $response = $this->putJson($this->updateUrl, []);
        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_ms_t_ar_m_up_d_002_empty_payload()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        // Empty payload should be valid (no op)
        $response = $this->call('PUT', $this->updateUrl, [], $cookies);
        if ($response->status() !== CommonVal::HTTP_OK) {
            $response->dump();
        }
        $response->assertStatus(CommonVal::HTTP_OK);
    }

    public function test_ms_t_ar_m_up_d_003_invalid_structure()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = ['insert' => 'not-an-array'];
        $response = $this->call('PUT', $this->updateUrl, $payload, $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    }

    public function test_ms_t_ar_m_up_d_005_self_edit_forbidden()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);
        $newRole = RoleMst::factory()->create();

        // Try to add a role to SELF
        $payload = [
            'insert' => [
                ['admin_mst_id' => $admin->id, 'role_mst_id' => $newRole->id],
            ],
        ];

        $response = $this->call('PUT', $this->updateUrl, $payload, $cookies);
        if ($response->status() !== CommonVal::HTTP_UNPROCESSABLE_CONTENT) {
            $response->dump();
        }
        // Expect LogicException E0018 converted to 422
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $response->assertJsonFragment(['messages' => \App\Constants\Messages::E0018]);
        // Or check generic error structure if messages key differs
    }

    public function test_ms_t_ar_m_up_d_006_insert_success()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $targetAdmin = AdminMst::factory()->create();
        $role = RoleMst::factory()->create();

        $payload = [
            'insert' => [
                ['admin_mst_id' => $targetAdmin->id, 'role_mst_id' => $role->id],
            ],
        ];

        $response = $this->call('PUT', $this->updateUrl, $payload, $cookies);
        if ($response->status() !== CommonVal::HTTP_OK) {
            $response->dump();
        }
        $response->assertStatus(CommonVal::HTTP_OK);

        $this->assertDatabaseHas('admin_role_mst', [
            'admin_mst_id' => $targetAdmin->id,
            'role_mst_id' => $role->id,
        ]);
    }

    public function test_ms_t_ar_m_up_d_008_delete_success()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $targetAdmin = AdminMst::factory()->create();
        $role = RoleMst::factory()->create();

        // Pre-exist
        DB::table('admin_role_mst')->insert([
            'admin_mst_id' => $targetAdmin->id,
            'role_mst_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'delete' => [
                ['admin_mst_id' => $targetAdmin->id, 'role_mst_id' => $role->id],
            ],
        ];

        $response = $this->call('PUT', $this->updateUrl, $payload, $cookies);
        if ($response->status() !== CommonVal::HTTP_OK) {
            $response->dump();
        }
        $response->assertStatus(CommonVal::HTTP_OK);

        $this->assertDatabaseMissing('admin_role_mst', [
            'admin_mst_id' => $targetAdmin->id,
            'role_mst_id' => $role->id,
        ]);
    }
}
