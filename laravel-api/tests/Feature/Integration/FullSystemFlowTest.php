<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class FullSystemFlowTest extends TestCase
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

    private function getAuthCookies(AdminMst $admin): array
    {
        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);

        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return $cookies;
    }

    private function grantAccessToAdmin(AdminMst $admin, string $method, string $path)
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

        $feature = FeatureMst::firstOrCreate([
            'name' => 'Integration Feature',
            'group_name' => 'System',
            'status' => 1,
            'is_delete' => 0,
        ]);

        $typeMap = ['GET' => 0, 'POST' => 1, 'PUT' => 2, 'DELETE' => 4];
        $type = $typeMap[strtoupper($method)] ?? 0;

        // Use a unique name for each path to avoid unique constraint if re-using 'Int Test POST'
        $pathHash = mb_substr(md5($path), 0, 6); // Short hash
        $api = ApiMst::firstOrCreate(
            ['path' => $path, 'type' => $type],
            [
                'name' => "IT $method $pathHash",
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

    public function test_scenario_3_token_security()
    {
        $admin = AdminMst::factory()->create();

        $checkUrl = 'api/admin/token-mst/list';
        $this->grantAccessToAdmin($admin, 'POST', $this->loginUrl);
        $this->grantAccessToAdmin($admin, 'GET', $checkUrl);

        // Login
        $cookies = $this->getAuthCookies($admin);
        $accessToken = $cookies['access_token'] ?? null;
        $this->assertNotNull($accessToken);

        // 2. Verify Access
        $response = $this->call('GET', $checkUrl, [], $cookies);
        $response->assertStatus(200);

        // 3. Manually Expire Token (Revoke from Redis)
        $tokenKey = CommonVal::ADMIN_TYPE.":{$admin->id}:{$accessToken}";
        $deleted = Redis::del($tokenKey);

        // 4. Access Retry (Should Fail)
        $response = $this->call('GET', $checkUrl, [], $cookies);

        // Assert failure (401 or 403)
        $this->assertNotEquals(200, $response->status(), 'Token should be expired');
    }
}
