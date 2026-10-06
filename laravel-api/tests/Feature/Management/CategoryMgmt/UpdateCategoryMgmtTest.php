<?php

namespace Tests\Feature\Management\CategoryMgmt;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use App\Models\Management\CategoryMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpdateCategoryMgmtTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/category-mgmt/update/';

    /**
     * Helper to get authenticated cookies with 'root' role
     */
    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        $this->grantAccessTo($rootRole, 'PUT', 'api/admin/category-mgmt/update/{id}');

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
     * Test [CAT_MEM_UPD_001] Unauthenticated
     */
    public function test_ca_t_me_m_up_d_001_unauthenticated()
    {
        $response = $this->putJson($this->baseUrl.'1', []);
        $response->assertStatus(401);
    }

    /**
     * Test [CAT_MEM_UPD_002] Not Found
     */
    public function test_ca_t_me_m_up_d_002_not_found()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'id' => 99999,
            'parent_id' => 0,
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'status' => StatusEnum::PUBLISHED->value,
            'is_display' => IsActive::TRUE->value,
            'rank_order' => 1,
            'is_delete' => IsDelete::FALSE->value,
        ];

        $response = $this->call('PUT', $this->baseUrl.'99999', $payload, $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertArrayHasKey('id', $response->json('error.messages'));
    }

    /**
     * Test [CAT_MEM_UPD_003] Invalid Data
     */
    public function test_ca_t_me_m_up_d_003_invalid_data()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);
        $category = CategoryMgmt::factory()->create();

        $payload = [
            'id' => $category->id,
            'parent_id' => 0,
            'name' => str_repeat('a', 51),
            'slug' => 'slug',
            'status' => StatusEnum::PUBLISHED->value,
            'is_display' => IsActive::TRUE->value,
            'rank_order' => 1,
            'is_delete' => IsDelete::FALSE->value,
        ];

        $response = $this->call('PUT', $this->baseUrl.$category->id, $payload, $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertArrayHasKey('name', $response->json('error.messages'));
    }

    /**
     * Test [CAT_MEM_UPD_004] Success
     */
    public function test_ca_t_me_m_up_d_004_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);
        $category = CategoryMgmt::factory()->create();

        $payload = [
            'id' => $category->id,
            'parent_id' => 0,
            'name' => 'Updated Category',
            'slug' => 'updated-category-slug',
            'description' => 'Updated Desc',
            'status' => StatusEnum::DRAFT->value,
            'is_display' => IsActive::FALSE->value,
            'rank_order' => 2,
            'is_delete' => IsDelete::FALSE->value,
        ];

        $response = $this->call('PUT', $this->baseUrl.$category->id, $payload, $cookies);

        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);

        // Verify ID returned (directly or in data)
        // Store returned directly, Update likely does too or returns affected count.
        // Controller: return $this->categoryMgmt->update($payload); -> return $affected (int 1 usually)
        // Let's assert status 200 first.

        $this->assertDatabaseHas('category_mgmt', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'status' => StatusEnum::DRAFT->value,
            'is_display' => 0,
        ]);

        // Verify History
        $this->assertDatabaseHas('category_mgmt_hist', [
            'category_mgmt_id' => $category->id,
            'name' => 'Updated Category',
            'action' => 2, // Update
        ]);
    }
}
