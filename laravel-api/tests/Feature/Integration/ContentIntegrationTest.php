<?php

namespace Tests\Feature\Integration;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ContentIntegrationTest extends TestCase
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

    public function test_content_site_refresh_flow()
    {
        // 1. Setup Admin & Permissions
        $admin = AdminMst::factory()->create();

        // Pre-grant ALL permissions
        $this->ensureRootAccess($admin, 'POST', $this->loginUrl);

        $this->ensureRootAccess($admin, 'POST', 'api/admin/slider-mgmt/store');
        $this->ensureRootAccess($admin, 'PUT', 'api/admin/slider-mgmt/update/{id}');
        $this->ensureRootAccess($admin, 'POST', 'api/admin/slider-mgmt/delete');

        $this->ensureRootAccess($admin, 'POST', 'api/admin/social-mgmt/store');
        $this->ensureRootAccess($admin, 'GET', 'api/admin/social-mgmt/list');
        $this->ensureRootAccess($admin, 'POST', 'api/admin/social-mgmt/delete');

        // Login (Populate Redis)
        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        // 2. Upload Media (Skipped - Use string)
        $mediaUrl = 'https://test.com/generated-image.jpg';

        // 3. Create Slider
        $sliderStoreUrl = 'api/admin/slider-mgmt/store';

        $sliderPayload = [
            'title' => 'New Season Promo',
            'slug' => 'new-season',
            'link' => 'https://site.com/promo',
            'image' => $mediaUrl,
            'status' => 1,
            'is_delete' => 0,
        ];
        $sliderResp = $this->call('POST', $sliderStoreUrl, $sliderPayload, $cookies);
        $sliderResp->assertStatus(200);
        $sliderId = $sliderResp->json('data');

        // 4. Create Social
        $socialStoreUrl = 'api/admin/social-mgmt/store';
        $socialPayload = [
            'name' => 'Instagram',
            'slug' => 'instagram-feed',
            'link' => 'https://instagram.com/site',
            'image' => $mediaUrl,
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 10,
            'is_delete' => 0,
        ];
        $socialResp = $this->call('POST', $socialStoreUrl, $socialPayload, $cookies);
        $socialResp->assertStatus(200);
        $socialId = $socialResp->json('data');

        // 5. Cross-Check
        $listUrl = 'api/admin/social-mgmt/list';
        $listResp = $this->call('GET', $listUrl, [], $cookies);
        $listResp->assertStatus(200);
        $this->assertTrue(collect($listResp->json('data.data'))->contains('id', $socialId));

        // 6. Update Slider
        $sliderUpdateUrl = 'api/admin/slider-mgmt/update/'.$sliderId;
        $sliderPayload['status'] = 0; // Draft
        $sliderPayload['id'] = $sliderId;

        $updateResp = $this->call('PUT', $sliderUpdateUrl, $sliderPayload, $cookies);
        $updateResp->assertStatus(200);
        $this->assertDatabaseHas('slider_mgmt', ['id' => $sliderId, 'status' => 0]);

        // 7. Bulk Cleanup
        $deleteSocialUrl = 'api/admin/social-mgmt/delete';
        $this->call('POST', $deleteSocialUrl, ['ids' => [$socialId]], $cookies)
            ->assertStatus(200);
        $this->assertDatabaseHas('social_mgmt', ['id' => $socialId, 'is_delete' => 1]);

        $deleteSliderUrl = 'api/admin/slider-mgmt/delete';
        $this->call('POST', $deleteSliderUrl, ['ids' => [$sliderId]], $cookies)
            ->assertStatus(200);

        // 8. Verify Hist
        $this->assertDatabaseHas('social_mgmt_hist', ['social_mgmt_id' => $socialId, 'action' => 3]);
        $this->assertDatabaseHas('slider_mgmt_hist', ['slider_mgmt_id' => $sliderId, 'action' => 1]);
    }
}
