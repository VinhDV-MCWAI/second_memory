<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\BannerMgmtHist;

use App\Constants\CommonVal;
use App\Enums\ActionType;
use App\Enums\StatusEnum;
use App\Models\Management\BannerMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreBannerMgmtHistTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/banner-mgmt-hist/store';

    /**
     * Helper to get authenticated cookies with 'root' role
     */
    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        $this->grantAccessTo($rootRole, 'POST', $this->baseUrl);

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
     * Test [HST_BNR_STR_001] Unauthenticated
     */
    public function test_hs_t_bn_r_st_r_001_unauthenticated()
    {
        $response = $this->postJson($this->baseUrl, []);
        $response->assertStatus(401);
    }

    /**
     * Test [HST_BNR_STR_002] Missing Required Fields
     */
    public function test_hs_t_bn_r_st_r_002_missing_required_fields()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('POST', $this->baseUrl, [], $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertArrayHasKey('banner_mgmt_id', $response->json('error.messages'));
        $this->assertArrayHasKey('action', $response->json('error.messages'));
        $this->assertArrayHasKey('author_id', $response->json('error.messages'));
    }

    /**
     * Test [HST_BNR_STR_003] Success
     */
    public function test_hs_t_bn_r_st_r_003_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);
        $banner = BannerMgmt::factory()->create();

        $payload = [
            'banner_mgmt_id' => $banner->id,
            'title' => 'History Title',
            'slug' => 'history-slug',
            'description' => 'History Description',
            'link' => 'https://history.com',
            'image' => 'history.jpg',
            'position' => 'top',
            'status' => StatusEnum::PUBLISHED->value,
            'action' => ActionType::CREATE->value,
            'author_id' => $admin->id,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);

        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);

        // Assert ID returned (either int or wrapped)
        // StoreBannerMgmt returned int directly. Let's see here.
        // If it fails, I'll adjust.
        $id = $response->json();
        $this->assertNotNull($id);

        $this->assertDatabaseHas('banner_mgmt_hist', [
            'banner_mgmt_id' => $banner->id,
            'title' => 'History Title',
            'author_id' => $admin->id,
        ]);
    }
}
