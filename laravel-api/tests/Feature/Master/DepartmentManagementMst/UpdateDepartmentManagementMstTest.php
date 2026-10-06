<?php

namespace Tests\Feature\Master\DepartmentManagementMst;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\DepartmentManagementMst;
use App\Models\Master\DepartmentMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\PolicyDepartmentMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpdateDepartmentManagementMstTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/department-management-mst/update';

    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        $this->grantAccessTo($rootRole, 'PUT', $this->baseUrl);

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
                'name' => substr("Endp $method $path", 0, 50),
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

    public function test_dp_t_mg_t_up_d_001_unauthenticated()
    {
        $response = $this->putJson($this->baseUrl, []);
        $response->assertStatus(401);
    }

    public function test_dp_t_mg_t_up_d_002_invalid_data()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        // Test with invalid types
        $payload = [
            'insert' => 'invalid',
            'delete' => 'invalid',
        ];
        $response = $this->call('PUT', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);

        // Test with missing required fields in array
        $payload = [
            'insert' => [
                ['department_mst_id' => 1], // missing policy_department_mst_id
            ],
        ];
        $response = $this->call('PUT', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
    }

    public function test_dp_t_mg_t_up_d_003_success_insert()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $dep = DepartmentMst::factory()->create();
        $policy = PolicyDepartmentMst::factory()->create();

        $payload = [
            'insert' => [
                [
                    'department_mst_id' => $dep->id,
                    'policy_department_mst_id' => $policy->id,
                ],
            ],
            'delete' => [],
        ];

        $response = $this->call('PUT', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);

        $this->assertDatabaseHas('department_management_mst', [
            'department_mst_id' => $dep->id,
            'policy_department_mst_id' => $policy->id,
        ]);
    }

    public function test_dp_t_mg_t_up_d_004_success_delete()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $dep = DepartmentMst::factory()->create();
        $policy = PolicyDepartmentMst::factory()->create();

        DepartmentManagementMst::create([
            'department_mst_id' => $dep->id,
            'policy_department_mst_id' => $policy->id,
        ]);

        $payload = [
            'insert' => [],
            'delete' => [
                [
                    'department_mst_id' => $dep->id,
                    'policy_department_mst_id' => $policy->id,
                ],
            ],
        ];

        $response = $this->call('PUT', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);

        $this->assertDatabaseMissing('department_management_mst', [
            'department_mst_id' => $dep->id,
            'policy_department_mst_id' => $policy->id,
        ]);
    }

    public function test_dp_t_mg_t_up_d_005_success_mixed()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        // Pre-existing (to be deleted)
        $depDel = DepartmentMst::factory()->create();
        $policyDel = PolicyDepartmentMst::factory()->create();
        DepartmentManagementMst::create([
            'department_mst_id' => $depDel->id,
            'policy_department_mst_id' => $policyDel->id,
        ]);

        // New (to be inserted)
        $depNew = DepartmentMst::factory()->create();
        $policyNew = PolicyDepartmentMst::factory()->create();

        $payload = [
            'insert' => [
                [
                    'department_mst_id' => $depNew->id,
                    'policy_department_mst_id' => $policyNew->id,
                ],
            ],
            'delete' => [
                [
                    'department_mst_id' => $depDel->id,
                    'policy_department_mst_id' => $policyDel->id,
                ],
            ],
        ];

        $response = $this->call('PUT', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);

        $this->assertDatabaseMissing('department_management_mst', [
            'department_mst_id' => $depDel->id,
            'policy_department_mst_id' => $policyDel->id,
        ]);

        $this->assertDatabaseHas('department_management_mst', [
            'department_mst_id' => $depNew->id,
            'policy_department_mst_id' => $policyNew->id,
        ]);
    }
}
