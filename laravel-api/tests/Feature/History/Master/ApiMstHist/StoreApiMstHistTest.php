<?php

namespace Tests\Feature\History\Master\ApiMstHist;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreApiMstHistTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/api-mst-hist/store';

    /**
     * Helper to get authenticated cookies with 'root' role
     */
    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = \App\Models\Master\RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = \App\Models\Master\RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        $this->grantAccessTo($rootRole, 'POST', 'api/admin/api-mst-hist/store');

        if (! \Illuminate\Support\Facades\DB::table('admin_role_mst')
            ->where('admin_mst_id', $admin->id)
            ->where('role_mst_id', $rootRole->id)
            ->exists()) {
            \Illuminate\Support\Facades\DB::table('admin_role_mst')->insert([
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

    private function grantAccessTo(\App\Models\Master\RoleMst $role, string $method, string $path)
    {
        $typeMap = ['GET' => 0, 'POST' => 1, 'PUT' => 2, 'PATCH' => 3, 'DELETE' => 4];
        $type = $typeMap[strtoupper($method)] ?? 0;

        $feature = \App\Models\Master\FeatureMst::firstOrCreate([
            'name' => 'System Features',
            'group_name' => 'System',
            'status' => 1,
            'is_delete' => 0,
        ]);

        $api = \App\Models\Master\ApiMst::firstOrCreate(
            ['path' => $path, 'type' => $type],
            [
                'name' => "Endpoint $method $path",
                'is_active' => 1,
                'feature_mst_id' => $feature->id,
                'is_delete' => 0,
            ]
        );

        \Illuminate\Support\Facades\DB::table('api_role_mst')->insertOrIgnore([
            'api_mst_id' => $api->id,
            'role_mst_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test [MST_HIST_STR_001] Wrong HTTP Method
     */
    public function test_ms_t_his_t_st_r_001_wrong_http_method()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(405);
    }

    /**
     * Test [MST_HIST_STR_002] Unauthenticated
     */
    public function test_ms_t_his_t_st_r_002_unauthenticated()
    {
        $response = $this->postJson($this->baseUrl, []);
        $response->assertStatus(401);
    }

    /**
     * Test [MST_HIST_STR_003] Required Fields Missing
     */
    public function test_ms_t_his_t_st_r_003_required_fields_missing()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('POST', $this->baseUrl, [], $cookies);

        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $errors = $response->json('error.messages');

        $this->assertArrayHasKey('api_mst_id', $errors);
        $this->assertArrayHasKey('feature_mst_id', $errors);
        $this->assertArrayHasKey('action', $errors);
        $this->assertArrayHasKey('author_id', $errors);
    }

    /**
     * Test [MST_HIST_STR_004] Max Length Fields
     */
    public function test_ms_t_his_t_st_r_004_max_length_fields()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => str_repeat('a', 51), // Max 50
            'path' => str_repeat('a', 101), // Max 100
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $errors = $response->json('error.messages');

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('path', $errors);
    }

    /**
     * Test [MST_HIST_STR_005] Non-existent FKs
     */
    public function test_ms_t_his_t_st_r_005_non_existent_fks()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'api_mst_id' => 99999,
            'feature_mst_id' => 99999,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $errors = $response->json('error.messages');

        $this->assertArrayHasKey('api_mst_id', $errors);
        $this->assertArrayHasKey('feature_mst_id', $errors);
    }

    /**
     * Test [MST_HIST_STR_006] Invalid Enum
     */
    public function test_ms_t_his_t_st_r_006_invalid_enum()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'is_active' => 99,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $errors = $response->json('error.messages');

        $this->assertArrayHasKey('is_active', $errors);
    }

    /**
     * Test [MST_HIST_STR_009] Success
     */
    public function test_ms_t_his_t_st_r_009_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $feature = FeatureMst::factory()->create();
        $api = ApiMst::factory()->create(['feature_mst_id' => $feature->id]);

        $payload = [
            'api_mst_id' => $api->id,
            'type' => 1,
            'name' => 'History Name',
            'path' => '/hist/path',
            'is_active' => IsActive::TRUE->value,
            'feature_mst_id' => $feature->id,
            'action' => 1,
            'author_id' => $admin->id,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);

        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);

        $this->assertDatabaseHas('api_mst_hist', [
            'api_mst_id' => $api->id,
            'name' => 'History Name',
            'path' => '/hist/path',
            'action' => 1,
            'author_id' => $admin->id,
        ]);
    }
}
