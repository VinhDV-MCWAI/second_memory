<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\SettingLinkMgmtHist;

use App\Models\History\Management\SettingLinkMgmtHist;
use App\Models\Management\SettingLinkMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ListSettingLinkMgmtHistTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/setting-link-mgmt-hist/list';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }

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

    public function test_se_t_ln_k_hs_t_ls_t_001_unauthenticated()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(401);
    }

    public function test_se_t_ln_k_hs_t_ls_t_002_success_list()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $setting = SettingLinkMgmt::factory()->create();
        SettingLinkMgmtHist::create([
            'setting_link_mgmt_id' => $setting->id,
            'key' => $setting->key,
            'value' => $setting->value,
            'action' => 1,
            'author_id' => $admin->id,
            'created_at' => now(),
        ]);

        $response = $this->call('GET', $this->baseUrl, [], $cookies);
        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertGreaterThanOrEqual(1, count($data));

        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('setting_link_mgmt_id', $data[0]);
        $this->assertArrayHasKey('action', $data[0]);
    }

    public function test_se_t_ln_k_hs_t_ls_t_003_filter_by_setting()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $setting1 = SettingLinkMgmt::factory()->create();
        $setting2 = SettingLinkMgmt::factory()->create();

        SettingLinkMgmtHist::create([
            'setting_link_mgmt_id' => $setting1->id,
            'key' => $setting1->key,
            'value' => $setting1->value,
            'action' => 1,
            'author_id' => $admin->id,
            'created_at' => now(),
        ]);

        $response = $this->call('GET', $this->baseUrl, ['setting_link_mgmt_id' => $setting1->id], $cookies);
        $response->assertStatus(200);

        $data = $response->json('data.data');
        foreach ($data as $item) {
            $this->assertEquals($setting1->id, $item['setting_link_mgmt_id']);
        }
    }
}
