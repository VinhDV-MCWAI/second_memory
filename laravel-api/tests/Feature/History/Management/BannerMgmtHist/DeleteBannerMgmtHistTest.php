<?php

namespace Tests\Feature\History\Management\BannerMgmtHist;

use App\Constants\CommonVal;
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

class DeleteBannerMgmtHistTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/banner-mgmt-hist/delete';

    /**
     * Helper to get authenticated cookies with 'root' role
     */
    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        // Grant access to DELETE endpoint
        $this->grantAccessTo($rootRole, 'POST', 'api/admin/banner-mgmt-hist/delete');

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
     * Test [HST_BNR_DEL_001] Unauthenticated
     */
    public function test_hs_t_bn_r_de_l_001_unauthenticated()
    {
        $response = $this->postJson($this->baseUrl, []);
        $response->assertStatus(401);
    }

    /**
     * Test [HST_BNR_DEL_002] Invalid - Missing IDs
     */
    public function test_hs_t_bn_r_de_l_002_invalid()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('POST', $this->baseUrl, [], $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertArrayHasKey('ids', $response->json('error.messages'));
    }

    /**
     * Test [HST_BNR_DEL_003] Success
     */
    public function test_hs_t_bn_r_de_l_003_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);
        $banner = BannerMgmt::factory()->create();

        $history1 = BannerMgmtHist::create([
            'banner_mgmt_id' => $banner->id,
            'title' => 'Hist 1',
            'slug' => 'slug',
            'status' => 1,
            'action' => ActionType::CREATE->value,
            'author_id' => $admin->id,
            'created_at' => now(),
        ]);

        $history2 = BannerMgmtHist::create([
            'banner_mgmt_id' => $banner->id,
            'title' => 'Hist 2',
            'slug' => 'slug',
            'status' => 1,
            'action' => ActionType::UPDATE->value,
            'author_id' => $admin->id,
            'created_at' => now(),
        ]);

        $ids = [$history1->id, $history2->id];
        $payload = ['ids' => $ids];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);

        // Verify deletion
        // Does History support soft delete?
        // BannerMgmtHist model does NOT have SoftDeletes trait in my previous view.
        // It has `const UPDATED_AT = null;`.
        // Let's check `BannerMgmtHist` model again.
        // If no SoftDeletes, it should be hard deleted.
        // But `BannerMgmtHist` migration usually doesn't have `deleted_at`.
        // However, `AdminMst` has SoftDeletes.
        // I will assume hard delete unless I see SoftDeletes trait in BannerMgmtHist.

        // Checking model:
        // class BannerMgmtHist extends Model { ... }
        // It does NOT use SoftDeletes.

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('banner_mgmt_hist', ['id' => $id]);
        }
    }
}
