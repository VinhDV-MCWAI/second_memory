<?php

declare(strict_types=1);

namespace Tests\Feature\Management\SettingLinkMgmt;

use App\Models\Management\SettingLinkMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ListSettingLinkMgmtTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/setting-link-mgmt/list';

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

    public function test_se_t_ln_k_ls_t_001_unauthenticated()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(401);
    }

    public function test_se_t_ln_k_ls_t_002_success_list()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        SettingLinkMgmt::factory()->count(2)->create();

        $response = $this->call('GET', $this->baseUrl, [], $cookies);
        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertGreaterThanOrEqual(2, count($data));

        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('key', $data[0]);
        $this->assertArrayHasKey('value', $data[0]);
    }

    public function test_se_t_ln_k_ls_t_003_filter_by_key()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        SettingLinkMgmt::factory()->create(['key' => 'search_key']);
        SettingLinkMgmt::factory()->create(['key' => 'other_key']);

        $response = $this->call('GET', $this->baseUrl, ['key' => 'search_key'], $cookies);
        $response->assertStatus(200);

        $data = $response->json('data.data');
        $keys = array_column($data, 'key');
        $this->assertContains('search_key', $keys);
    }
}
