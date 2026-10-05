<?php

namespace Tests\Feature\History\Management\EntryMgmtHist;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use App\Models\Management\EntryMgmt;
use App\Models\History\Management\EntryMgmtHist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class StoreEntryMgmtHistTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/entry-mgmt-hist/store';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }

    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (!$rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        $this->grantAccessTo($rootRole, 'POST', $this->baseUrl);

        if (!DB::table('admin_role_mst')
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
        $typeMap = ['POST' => 0, 'POST' => 1, 'PUT' => 2, 'PATCH' => 3, 'DELETE' => 4];
        $type = $typeMap[strtoupper($method)] ?? 0;

        $feature = FeatureMst::firstOrCreate([
            'name' => 'System Features',
            'group_name' => 'System',
            'status' => 1,
            'is_delete' => 0
        ]);

        $api = ApiMst::firstOrCreate(
            ['path' => $path, 'type' => $type],
            [
                'name' => substr("Endp $method $path", 0, 50),
                'is_active' => 1,
                'feature_mst_id' => $feature->id,
                'is_delete' => 0
            ]
        );

        DB::table('api_role_mst')->insertOrIgnore([
            'api_mst_id' => $api->id,
            'role_mst_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_SKL_HST_STO_001_unauthenticated()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(401);
    }

    public function test_SKL_HST_STO_002_success_list()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $desc = EntryMgmt::factory()->create();
        EntryMgmtHist::create([
            'entry_description_mgmt_id' => $desc->id,
            'parent_id' => 0,
            'title' => $desc->title,
            'summary' => $desc->summary,
            'article' => $desc->article,
            'status' => $desc->status,
            'is_display' => $desc->is_display,
            'rank_order' => $desc->rank_order,
            'action' => 1,
            'author_id' => $admin->id,
            'created_at' => now(),
        ]);

        $response = $this->call('POST', $this->baseUrl, [], $cookies);
        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertGreaterThanOrEqual(1, count($data));
        
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('entry_description_mgmt_id', $data[0]);
        $this->assertArrayHasKey('action', $data[0]);
    }

    public function test_SKL_HST_STO_003_filter_by_desc()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $desc1 = EntryMgmt::factory()->create();
        $desc2 = EntryMgmt::factory()->create();
        
        EntryMgmtHist::create([
            'entry_description_mgmt_id' => $desc1->id,
            'parent_id' => 0,
            'title' => $desc1->title,
            'summary' => $desc1->summary,
            'article' => $desc1->article,
            'status' => $desc1->status,
            'is_display' => $desc1->is_display,
            'rank_order' => $desc1->rank_order,
            'action' => 1,
            'author_id' => $admin->id,
            'created_at' => now(),
        ]);

        $response = $this->call('POST', $this->baseUrl, ['entry_description_mgmt_id' => $desc1->id], $cookies);
        $response->assertStatus(200);
        
        $data = $response->json('data.data');
        foreach ($data as $item) {
            $this->assertEquals($desc1->id, $item['entry_description_mgmt_id']);
        }
    }
}
