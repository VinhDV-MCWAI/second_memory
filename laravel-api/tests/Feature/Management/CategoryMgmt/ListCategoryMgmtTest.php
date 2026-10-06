<?php

declare(strict_types=1);

namespace Tests\Feature\Management\CategoryMgmt;

use App\Constants\CommonVal;
use App\Enums\StatusEnum;
use App\Models\Management\CategoryMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListCategoryMgmtTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/category-mgmt/list';

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
     * Test [CAT_MEM_LST_001] Unauthenticated
     */
    public function test_ca_t_me_m_ls_t_001_unauthenticated()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(401);
    }

    /**
     * Test [CAT_MEM_LST_002] Invalid Date Format
     */
    public function test_ca_t_me_m_ls_t_002_invalid_date_format()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl, ['from_date' => 'invalid-date'], $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertArrayHasKey('from_date', $response->json('error.messages'));
    }

    /**
     * Test [CAT_MEM_LST_003] To Date Before From Date
     */
    public function test_ca_t_me_m_ls_t_003_to_date_before_from_date()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $params = [
            'from_date' => '2025/12/31',
            'to_date' => '2025/01/01',
        ];

        $response = $this->call('GET', $this->baseUrl, $params, $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertArrayHasKey('to_date', $response->json('error.messages'));
    }

    /**
     * Test [CAT_MEM_LST_004] Filter Success
     */
    public function test_ca_t_me_m_ls_t_004_filter_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $category1 = CategoryMgmt::factory()->create(['name' => 'Alpha Category', 'status' => StatusEnum::PUBLISHED->value]);
        $category2 = CategoryMgmt::factory()->create(['name' => 'Beta Category', 'status' => StatusEnum::DRAFT->value]);

        // Filter by name
        $response = $this->call('GET', $this->baseUrl, ['name' => 'Alpha'], $cookies);

        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals($category1->id, $data[0]['id']);

        // Filter by status
        $response = $this->call('GET', $this->baseUrl, ['status' => StatusEnum::DRAFT->value], $cookies);
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals($category2->id, $data[0]['id']);
    }

    /**
     * Test [CAT_MEM_LST_005] Success No Filters
     */
    public function test_ca_t_me_m_ls_t_005_success_no_filters()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        CategoryMgmt::factory()->count(3)->create();

        $response = $this->call('GET', $this->baseUrl, [], $cookies);
        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.data'));
    }
}
