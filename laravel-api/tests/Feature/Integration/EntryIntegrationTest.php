<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class EntryIntegrationTest extends TestCase
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

    public function test_entry_catalog_building_flow()
    {
        $admin = AdminMst::factory()->create();

        $this->ensureRootAccess($admin, 'POST', $this->loginUrl);
        $this->ensureRootAccess($admin, 'POST', 'api/admin/category-mgmt/store');
        $this->ensureRootAccess($admin, 'POST', 'api/admin/entry-mgmt/store');
        $this->ensureRootAccess($admin, 'POST', 'api/admin/entry-description-mgmt/store');

        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        // 2. Create Category
        $catPayload = [
            'parent_id' => 0,
            'name' => 'Programming',
            'slug' => 'programming',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];
        $catResp = $this->call('POST', 'api/admin/category-mgmt/store', $catPayload, $cookies);
        $catResp->assertStatus(200);
        $catId = $catResp->json('data');

        // 3. Create Entry
        $entryPayload = [
            'parent_id' => 0,
            'name' => 'PHP',
            'slug' => 'php-lang',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];
        $entryResp = $this->call('POST', 'api/admin/entry-mgmt/store', $entryPayload, $cookies);
        $entryResp->assertStatus(200);
        $entryId = $entryResp->json('data');

        // 4. Add Description (Fixed Payload)
        $descPayload = [
            'parent_id' => 0,
            'title' => 'PHP Language',
            'summary' => 'Short summary.',
            'article' => 'Full article content.',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];
        $descResp = $this->call('POST', 'api/admin/entry-description-mgmt/store', $descPayload, $cookies);
        $descResp->assertStatus(200);
    }

    public function test_prevent_duplicate_slugs()
    {
        $admin = AdminMst::factory()->create();
        $this->ensureRootAccess($admin, 'POST', $this->loginUrl);
        $this->ensureRootAccess($admin, 'POST', 'api/admin/category-mgmt/store');

        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        $catPayload = [
            'parent_id' => 0,
            'name' => 'Original',
            'slug' => 'unique-slug',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];
        $this->call('POST', 'api/admin/category-mgmt/store', $catPayload, $cookies)->assertStatus(200);

        // Attempt duplicate
        $dupResp = $this->call('POST', 'api/admin/category-mgmt/store', $catPayload, $cookies);
        // Expect 422 Unprocessable (Validation)
        $dupResp->assertStatus(422);
        // $dupResp->assertJsonValidationErrors(['slug'], 'error.messages');
    }
}
