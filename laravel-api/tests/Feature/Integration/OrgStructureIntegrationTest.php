<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class OrgStructureIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private string $loginUrl = '/api/admin/credential/login';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
        if (! RoleMst::where('name', 'root')->exists()) {
            RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }
    }

    private function ensureRootAccess(AdminMst $admin, string $method, string $path)
    {
        $role = RoleMst::where('name', 'root')->first();

        if (! DB::table('admin_role_mst')->where('admin_mst_id', $admin->id)->exists()) {
            DB::table('admin_role_mst')->insert([
                'admin_mst_id' => $admin->id,
                'role_mst_id' => $role->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $feature = FeatureMst::firstOrCreate(['name' => 'System'], [
            'name' => 'System',
            'group_name' => 'System',
            'status' => 1,
            'is_delete' => 0,
        ]);

        $typeMap = ['GET' => 0, 'POST' => 1, 'PUT' => 2, 'DELETE' => 4];
        $type = $typeMap[strtoupper($method)] ?? 0;

        $api = ApiMst::firstOrCreate(
            ['path' => $path, 'type' => $type],
            [
                'name' => "API $method $path",
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

    public function test_org_structure_flow()
    {
        $admin = AdminMst::factory()->create();

        $this->ensureRootAccess($admin, 'POST', $this->loginUrl);
        $this->ensureRootAccess($admin, 'POST', 'api/admin/department-mst/store');
        $this->ensureRootAccess($admin, 'PUT', 'api/admin/admin-department-mst/update');
        $this->ensureRootAccess($admin, 'GET', 'api/admin/department-mst/list');

        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        $deptPayload = [
            'code' => 'DEPT001',
            'name' => 'Engineering',
            'status' => 1,
            'is_delete' => 0,
        ];

        $deptResp = $this->call('POST', 'api/admin/department-mst/store', $deptPayload, $cookies);
        $deptResp->assertStatus(200);
        $deptId = $deptResp->json('data');

        $assignPayload = [
            'insert' => [
                [
                    'admin_mst_id' => $admin->id,
                    'department_mst_id' => $deptId,
                ],
            ],
        ];
        $assignResp = $this->call('PUT', 'api/admin/admin-department-mst/update', $assignPayload, $cookies);
        $assignResp->assertStatus(200);

        $this->assertDatabaseHas('admin_department_mst', ['admin_mst_id' => $admin->id, 'department_mst_id' => $deptId]);
    }

    public function test_unique_code_constraint()
    {
        $admin = AdminMst::factory()->create();
        $this->ensureRootAccess($admin, 'POST', $this->loginUrl);
        $this->ensureRootAccess($admin, 'POST', 'api/admin/department-mst/store');

        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        $deptPayload = [
            'code' => 'HR001',
            'name' => 'Human Resources',
            'status' => 1,
            'is_delete' => 0,
        ];
        $this->call('POST', 'api/admin/department-mst/store', $deptPayload, $cookies)->assertStatus(200);

        // Duplicate Code
        $dupResp = $this->call('POST', 'api/admin/department-mst/store', $deptPayload, $cookies);
        $dupResp->assertStatus(422)
            ->assertJsonPath('error.messages.code.0', 'The code has already been taken.');
    }
}
