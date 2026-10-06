<?php

namespace Tests\Feature\Master\TokenMst;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use App\Models\Master\TokenMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class UpdateTokenMstTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/token-mst/update';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }

    private function getAuthCookies(AdminMst $admin, string $method = 'PUT'): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        $this->grantAccessTo($rootRole, $method, $this->baseUrl.'/{id}');

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

    private function createToken(): TokenMst
    {
        return TokenMst::create([
            'account_id' => 1,
            'device_name' => 'Test Device',
            'ip_address' => '127.0.0.1',
            'token_hash' => 'hash123',
        ]);
    }

    // ========== ROUTE LAYER TESTS ==========

    public function test_to_k_up_d_r001_wrong_http_method()
    {
        $admin = AdminMst::factory()->create();
        $token = $this->createToken();
        $cookies = $this->getAuthCookies($admin, 'POST');

        $response = $this->call('POST', $this->baseUrl.'/'.$token->id, [], $cookies);
        $response->assertStatus(405);
    }

    public function test_to_k_up_d_r002_missing_path_parameter()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('PUT', $this->baseUrl, [], $cookies);
        $response->assertStatus(404);
    }

    // ========== MIDDLEWARE LAYER TESTS ==========

    public function test_to_k_up_d_m001_unauthenticated()
    {
        $token = $this->createToken();
        $response = $this->putJson($this->baseUrl.'/'.$token->id, []);
        $response->assertStatus(401);
    }

    // ========== VALIDATION LAYER TESTS ===========

    public function test_to_k_up_d_v001_id_not_found()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);
        $payload = ['account_id' => 1];

        $response = $this->call('PUT', $this->baseUrl.'/999999', $payload, $cookies);
        $response->assertStatus(422);
    }

    // ========== SERVICE / DB LAYER TESTS ==========

    public function test_to_k_up_d_s001_success()
    {
        $admin = AdminMst::factory()->create();
        $token = $this->createToken();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'id' => $token->id,
            'account_id' => 2,
            'device_name' => 'Updated Device',
            'ip_address' => '1.2.3.4',
            'expired_at' => '31/12/2025',
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$token->id, $payload, $cookies);
        $response->assertStatus(200);

        $this->assertDatabaseHas('token_mst', [
            'id' => $token->id,
            'account_id' => 2,
            'device_name' => 'Updated Device',
        ]);
    }
}
