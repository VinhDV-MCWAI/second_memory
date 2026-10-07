<?php

declare(strict_types=1);

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

class ListBannerMgmtHistTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/banner-mgmt-hist/list';

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
     * Test [HST_BNR_LST_001] Unauthenticated
     */
    public function test_hs_t_bn_r_ls_t_001_unauthenticated()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(401);
    }

    /**
     * Test [HST_BNR_LST_003] Filter Success
     */
    public function test_hs_t_bn_r_ls_t_003_filter_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);
        $banner = BannerMgmt::factory()->create();

        // Create specific history
        $target = BannerMgmtHist::create([
            'banner_mgmt_id' => $banner->id,
            'title' => 'Target History',
            'slug' => 'slug',
            'status' => 1,
            'action' => ActionType::CREATE->value,
            'author_id' => $admin->id,
            'created_at' => now(),
        ]);

        $other = BannerMgmtHist::create([
            'banner_mgmt_id' => $banner->id,
            'title' => 'Other History',
            'slug' => 'slug',
            'status' => 1,
            'action' => ActionType::UPDATE->value,
            'author_id' => $admin->id,
            'created_at' => now(),
        ]);

        // Filter by title
        $response = $this->call('GET', $this->baseUrl, ['title' => 'Target'], $cookies);
        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals($target->id, $response->json('data.data.0.id'));

        // Filter by action
        $response = $this->call('GET', $this->baseUrl, ['action' => ActionType::UPDATE->value], $cookies);
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertTrue(collect($data)->contains('id', $other->id));
    }
}
