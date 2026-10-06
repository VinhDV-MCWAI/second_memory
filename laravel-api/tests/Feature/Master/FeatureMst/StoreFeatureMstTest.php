<?php

namespace Tests\Feature\Master\FeatureMst;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class StoreFeatureMstTest extends TestCase
{
    use DatabaseTransactions;

    protected string $storeUrl = '/api/admin/feature-mst/store';

    protected string $loginUrl = '/api/admin/credential/login';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    protected function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        // Grant access to STORE route BEFORE login
        $this->grantAccessTo($rootRole, 'POST', 'api/admin/feature-mst/store');

        if (! DB::table('admin_role_mst')->where('admin_mst_id', $admin->id)->where('role_mst_id', $rootRole->id)->exists()) {
            DB::table('admin_role_mst')->insert([
                'admin_mst_id' => $admin->id,
                'role_mst_id' => $rootRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

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

    protected function assertCustomValidationErrors($response, $keys)
    {
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $json = $response->json();
        $this->assertArrayHasKey('error', $json);
        $this->assertArrayHasKey('messages', $json['error']);

        foreach ((array) $keys as $key) {
            $this->assertArrayHasKey($key, $json['error']['messages']);
        }
    }

    public function test_ft_r_st_o_001_unauthenticated()
    {
        $response = $this->postJson($this->storeUrl, []);
        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_ft_r_st_o_002_missing_required_fields()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('POST', $this->storeUrl, [], $cookies);

        $this->assertCustomValidationErrors($response, ['name', 'group_name', 'status', 'is_delete']);
    }

    public function test_ft_r_st_o_003_invalid_enum_values()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Test Feature',
            'group_name' => 'Test Group',
            'description' => 'Test Description',
            'status' => 999,
            'is_delete' => 999,
        ];

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);

        $this->assertCustomValidationErrors($response, ['status', 'is_delete']);
    }

    public function test_ft_r_st_o_004_success()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'New Feature',
            'group_name' => 'Feature Group',
            'description' => 'Feature Description',
            'status' => StatusEnum::PUBLISHED->value,
            'is_delete' => IsDelete::FALSE->value,
        ];

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);
        $this->assertDatabaseHas('feature_mst', [
            'name' => 'New Feature',
            'group_name' => 'Feature Group',
        ]);
    }
}
