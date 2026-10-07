<?php

declare(strict_types=1);

namespace Tests\Feature\Management\EntryDescriptionMgmt;

use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class UpdateEntryDescriptionMgmtTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/entry-description-mgmt/update';

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

        $this->grantAccessTo($rootRole, 'PUT', $this->baseUrl.'/{id}');

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

    public function test_sk_l_ds_c_up_d_001_unauthenticated()
    {
        $response = $this->putJson($this->baseUrl.'/1', []);
        $response->assertStatus(401);
    }

    public function test_sk_l_ds_c_up_d_002_validation_errors()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);
        $desc = EntryDescriptionMgmt::factory()->create();

        $response = $this->call('PUT', $this->baseUrl.'/'.$desc->id, [], $cookies);
        $response->assertStatus(422);
    }

    public function test_sk_l_ds_c_up_d_003_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);
        $desc = EntryDescriptionMgmt::factory()->create(['title' => 'Old Title']);

        $payload = [
            'id' => $desc->id,
            'parent_id' => 0,
            'title' => 'Updated Title',
            'summary' => 'Updated Summary',
            'article' => 'Updated Article',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 2,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$desc->id, $payload, $cookies);
        $response->assertStatus(200);

        $this->assertDatabaseHas('entry_description_mgmt', [
            'id' => $desc->id,
            'title' => 'Updated Title',
        ]);

        $this->assertDatabaseHas('entry_description_mgmt_hist', [
            'entry_description_mgmt_id' => $desc->id,
            'action' => 2,
        ]);
    }
}
