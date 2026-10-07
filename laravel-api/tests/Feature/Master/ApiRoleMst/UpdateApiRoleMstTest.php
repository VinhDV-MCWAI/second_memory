<?php

declare(strict_types=1);

namespace Tests\Feature\Master\ApiRoleMst;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\ApiRoleMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst; // Pivot Model
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpdateApiRoleMstTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/api-role-mst/update';

    /**
     * Helper to get authenticated cookies with 'root' role
     */
    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        $this->grantAccessTo($rootRole, 'PUT', 'api/admin/api-role-mst/update');

        if (! DB::table('admin_role_mst')
            ->where('admin_mst_id', $admin->id)
            ->where('role_mst_id', $rootRole->id)
            ->exists()) {
            DB::table('admin_role_mst')->insert([
                'admin_mst_id' => $admin->id,
                'role_mst_id' => $rootRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->postJson('/api/admin/credential/login', [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);

        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return $cookies;
    }

    private function grantAccessTo(RoleMst $role, string $method, string $path)
    {
        $typeMap = ['GET' => 0, 'POST' => 1, 'PUT' => 2, 'PATCH' => 3, 'DELETE' => 4];
        $type = $typeMap[strtoupper($method)] ?? 0;

        $feature = FeatureMst::firstOrCreate([
            'name' => 'System Features',
            'group_name' => 'System',
            'status' => 1,
            'is_delete' => 0,
        ]);

        $api = ApiMst::firstOrCreate(
            ['path' => $path, 'type' => $type],
            [
                'name' => "Endpoint $method $path",
                'is_active' => 1,
                'feature_mst_id' => $feature->id,
                'is_delete' => 0,
            ]
        );

        DB::table('api_role_mst')->insertOrIgnore([
            'api_mst_id' => $api->id,
            'role_mst_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test [MST_AROLE_UPD_001] Wrong HTTP Method
     */
    public function test_ms_t_arol_e_up_d_001_wrong_http_method()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(405);
    }

    /**
     * Test [MST_AROLE_UPD_002] Unauthenticated
     */
    public function test_ms_t_arol_e_up_d_002_unauthenticated()
    {
        $response = $this->putJson($this->baseUrl, []);
        $response->assertStatus(401);
    }

    /**
     * Test [MST_AROLE_UPD_004] Invalid Structure (Not Array)
     */
    public function test_ms_t_arol_e_up_d_004_invalid_structure()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'insert' => 'not-array',
        ];

        $response = $this->call('PUT', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertArrayHasKey('insert', $response->json('error.messages'));
    }

    /**
     * Test [MST_AROLE_UPD_005] Missing Required Fields
     */
    public function test_ms_t_arol_e_up_d_005_missing_required_fields()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'insert' => [
                ['api_mst_id' => 1], // Missing role_mst_id
            ],
        ];

        $response = $this->call('PUT', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        // Error key format: insert.0.role_mst_id
        $this->assertArrayHasKey('insert.0.role_mst_id', $response->json('error.messages'));
    }

    /**
     * Test [MST_AROLE_UPD_007] Security: Update OWN role
     */
    public function test_ms_t_arol_e_up_d_007_update_own_role()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        // $admin is attached to 'root' role in getAuthCookies
        $rootRole = RoleMst::where('name', 'root')->first();

        $payload = [
            'insert' => [
                ['api_mst_id' => 999, 'role_mst_id' => $rootRole->id],
            ],
        ];

        $response = $this->call('PUT', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        // logic exception usually returns 422 with message E0018
        $this->assertEquals(CommonVal::HTTP_UNPROCESSABLE_CONTENT, $response->json('error.code'));
    }

    /**
     * Test [MST_AROLE_UPD_008] Insert Success
     */
    public function test_ms_t_arol_e_up_d_008_insert_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $role = RoleMst::factory()->create();
        $feature = FeatureMst::factory()->create();
        $api = ApiMst::factory()->create(['feature_mst_id' => $feature->id]);

        $payload = [
            'insert' => [
                ['api_mst_id' => $api->id, 'role_mst_id' => $role->id],
            ],
        ];

        $response = $this->call('PUT', $this->baseUrl, $payload, $cookies);
        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);

        $this->assertDatabaseHas('api_role_mst', [
            'api_mst_id' => $api->id,
            'role_mst_id' => $role->id,
        ]);
    }

    /**
     * Test [MST_AROLE_UPD_009] Delete Success
     */
    public function test_ms_t_arol_e_up_d_009_delete_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $role = RoleMst::factory()->create();
        $feature = FeatureMst::factory()->create();
        $api = ApiMst::factory()->create(['feature_mst_id' => $feature->id]);

        // Pre-create
        ApiRoleMst::create(['api_mst_id' => $api->id, 'role_mst_id' => $role->id]);

        $payload = [
            'delete' => [
                ['api_mst_id' => $api->id, 'role_mst_id' => $role->id],
            ],
        ];

        $response = $this->call('PUT', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);

        $this->assertDatabaseMissing('api_role_mst', [
            'api_mst_id' => $api->id,
            'role_mst_id' => $role->id,
        ]);
    }
}
