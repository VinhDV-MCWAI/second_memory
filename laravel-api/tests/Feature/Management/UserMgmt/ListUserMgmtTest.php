<?php

declare(strict_types=1);

namespace Tests\Feature\Management\UserMgmt;

use App\Models\Management\UserMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ListUserMgmtTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/user-mgmt/list';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();

        // Seed data
        UserMgmt::factory()->create(['user_name' => 'user1', 'email' => 'user1@example.com', 'first_name' => 'Foo']);
        UserMgmt::factory()->create(['user_name' => 'user2', 'email' => 'user2@example.com', 'first_name' => 'Bar']);
        UserMgmt::factory()->create(['user_name' => 'user3', 'email' => 'user3@example.com', 'first_name' => 'Baz', 'is_delete' => 1]);
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

    public function test_use_r_ls_t_s001_success_list_all()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl, [], $cookies);
        $response->assertStatus(200);
        // $response->assertJsonStructure(['data', 'links', 'meta']);
        $response->assertJsonStructure(['data']);

        // Should verify count. Active users: 2. Deleted user: 1 (should be excluded usually).
        $data = $response->json('data.data'); // Fix path
        $this->assertCount(2, $data);
    }

    public function test_use_r_ls_t_s002_filter_by_username()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        // dump(UserMgmt::all()->toArray());
        $response = $this->call('GET', $this->baseUrl, ['user_name' => 'user1'], $cookies);
        // dump($response->json());
        $response->assertStatus(200);

        $data = $response->json('data.data'); // Fix path
        $this->assertCount(1, $data);
        $this->assertEquals('user1', $data[0]['user_name']);
    }
}
