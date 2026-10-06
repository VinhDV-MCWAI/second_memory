<?php

namespace Tests\Feature\History\Management\BannerMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\BannerMgmtHist;
use App\Models\Management\BannerMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpdateBannerMgmtHistTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/banner-mgmt-hist/update/';

    /**
     * Helper to get authenticated cookies with 'root' role
     */
    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        // Grant access to UPDATE endpoint
        $this->grantAccessTo($rootRole, 'PUT', 'api/admin/banner-mgmt-hist/update/{id}');

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
     * Test [HST_BNR_UPD_001] Unauthenticated
     */
    public function test_hs_t_bn_r_up_d_001_unauthenticated()
    {
        $response = $this->putJson($this->baseUrl.'1', []);
        $response->assertStatus(401);
    }

    /**
     * Test [HST_BNR_UPD_003] Success
     */
    public function test_hs_t_bn_r_up_d_003_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);
        $banner = BannerMgmt::factory()->create();

        $history = BannerMgmtHist::create([
            'banner_mgmt_id' => $banner->id,
            'title' => 'Initial Title',
            'slug' => 'slug',
            'status' => 1,
            'action' => ActionType::CREATE->value,
            'author_id' => $admin->id,
            'created_at' => now(),
        ]);

        $payload = [
            'id' => $history->id,
            'banner_mgmt_id' => $banner->id,
            'title' => 'Updated History Title',
            'slug' => 'slug',
            'status' => 1,
            'action' => ActionType::UPDATE->value,
            'author_id' => $admin->id,
        ];

        $response = $this->call('PUT', $this->baseUrl.$history->id, $payload, $cookies);

        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);

        // Verify ID returned
        // Assuming update returns ID like other endpoints
        $id = $response->json();
        if (is_array($id)) {
            $id = $id['data'] ?? $id;
        } // Handle possibility of wrapped response
        $this->assertEquals($history->id, $id);

        $this->assertDatabaseHas('banner_mgmt_hist', [
            'id' => $history->id,
            'title' => 'Updated History Title',
        ]);
    }
}
