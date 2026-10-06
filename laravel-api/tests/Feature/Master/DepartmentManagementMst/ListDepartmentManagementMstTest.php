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

class ListDepartmentManagementMstTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/department-management-mst/list';

    /**
     * Helper to get authenticated cookies with 'root' role
     */
    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        $this->grantAccessTo($rootRole, 'GET', $this->baseUrl);

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

    /**
     * Test [DPT_MGT_LST_001] Unauthenticated
     */
    public function test_dp_t_mg_t_ls_t_001_unauthenticated()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(401);
    }

    /**
     * Test [DPT_MGT_LST_002] Success
     */
    public function test_dp_t_mg_t_ls_t_002_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $dep = DepartmentMst::factory()->create();
        $policy = PolicyDepartmentMst::factory()->create();

        DepartmentManagementMst::create([
            'department_mst_id' => $dep->id,
            'policy_department_mst_id' => $policy->id,
        ]);

        $response = $this->call('GET', $this->baseUrl, [], $cookies);

        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals($dep->id, $data[0]['department']['id']);
        $this->assertEquals($policy->id, $data[0]['policy']['id']);
    }

    /**
     * Test [DPT_MGT_LST_003] Filter By Department
     */
    public function test_dp_t_mg_t_ls_t_003_filter_by_department()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $dep1 = DepartmentMst::factory()->create();
        $dep2 = DepartmentMst::factory()->create();
        $policy = PolicyDepartmentMst::factory()->create();

        DepartmentManagementMst::create(['department_mst_id' => $dep1->id, 'policy_department_mst_id' => $policy->id]);
        DepartmentManagementMst::create(['department_mst_id' => $dep2->id, 'policy_department_mst_id' => $policy->id]);

        $response = $this->call('GET', $this->baseUrl, ['department_mst_id' => $dep1->id], $cookies);
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals($dep1->id, $data[0]['department']['id']);
    }
}
