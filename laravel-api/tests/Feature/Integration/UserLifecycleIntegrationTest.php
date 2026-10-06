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

class UserLifecycleIntegrationTest extends TestCase
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

    public function test_user_lifecycle_flow()
    {
        $admin = AdminMst::factory()->create();

        $this->ensureRootAccess($admin, 'POST', $this->loginUrl);
        $this->ensureRootAccess($admin, 'POST', 'api/admin/user-mgmt/store');
        $this->ensureRootAccess($admin, 'PUT', 'api/admin/user-mgmt/update/{id}');
        $this->ensureRootAccess($admin, 'POST', 'api/admin/user-mgmt/delete');

        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        // Use valid real domain for DNS check
        $userPayload = [
            'email' => 'integrationtest@gmail.com',
            'user_name' => 'end_user_001',
            'password' => 'Password123!',
            'first_name' => 'End',
            'last_name' => 'User',
            'status' => 1,
            'is_active' => 1,
            'is_delete' => 0,
            'gender' => 1,
            'birth' => '01/01/2000',
            'phone_number' => '0901234567',
        ];

        $storeResp = $this->call('POST', 'api/admin/user-mgmt/store', $userPayload, $cookies);
        $storeResp->assertStatus(200);
        $userId = $storeResp->json('data');

        // 3. Update User
        $updateUrl = 'api/admin/user-mgmt/update/'.$userId;
        $userPayload['first_name'] = 'UpdatedName';
        $userPayload['id'] = $userId;

        $updateResp = $this->call('PUT', $updateUrl, $userPayload, $cookies);
        $updateResp->assertStatus(200);
        $this->assertDatabaseHas('user_mgmt', ['id' => $userId, 'first_name' => 'UpdatedName']);

        // 4. Delete User
        $deleteUrl = 'api/admin/user-mgmt/delete';
        $deleteResp = $this->call('POST', $deleteUrl, ['ids' => [$userId]], $cookies);
        $deleteResp->assertStatus(200);

        $this->assertDatabaseHas('user_mgmt', ['id' => $userId, 'is_delete' => 1]);
    }

    public function test_prevent_duplicate_email()
    {
        $admin = AdminMst::factory()->create();
        $this->ensureRootAccess($admin, 'POST', $this->loginUrl);
        $this->ensureRootAccess($admin, 'POST', 'api/admin/user-mgmt/store');

        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        $userPayload = [
            'email' => 'duplicate@gmail.com',
            'user_name' => 'user001',
            'password' => 'Password123!',
            'first_name' => 'End',
            'last_name' => 'User',
            'status' => 1,
            'is_active' => 1,
            'is_delete' => 0,
            'gender' => 1,
            'birth' => '01/01/2000',
            'phone_number' => '0901234567',
        ];

        // First Create
        $this->call('POST', 'api/admin/user-mgmt/store', $userPayload, $cookies)->assertStatus(200);

        // Duplicate Create
        $dupPayload = $userPayload;
        $dupPayload['user_name'] = 'user002';
        $dupResp = $this->call('POST', 'api/admin/user-mgmt/store', $dupPayload, $cookies);

        $dupResp->assertStatus(422)
            ->assertJsonPath('error.messages.email.0', 'The email has already been taken.');
    }
}
