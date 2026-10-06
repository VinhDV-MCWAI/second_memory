<?php

declare(strict_types=1);

namespace Tests\Feature\Master\FeatureMst;

use App\Constants\CommonVal;
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

class ListFeatureMstTest extends TestCase
{
    use DatabaseTransactions;

    protected string $listUrl = '/api/admin/feature-mst/list';

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

        // Grant access to LIST route BEFORE login
        $this->grantAccessTo($rootRole, 'GET', 'api/admin/feature-mst/list');

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

    public function test_ft_r_ls_t_001_unauthenticated()
    {
        $response = $this->getJson($this->listUrl);
        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_ft_r_ls_t_002_invalid_date_format()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->listUrl, ['from_date' => '2020-01-01'], $cookies);

        $this->assertCustomValidationErrors($response, ['from_date']);
    }

    public function test_ft_r_ls_t_003_to_date_before_from_date()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $params = [
            'from_date' => '02/01/2020',
            'to_date' => '01/01/2020',
        ];

        $response = $this->call('GET', $this->listUrl, $params, $cookies);

        $this->assertCustomValidationErrors($response, ['to_date']);
    }

    public function test_ft_r_ls_t_004_filter_by_name()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $target = FeatureMst::factory()->create(['name' => 'target_feature']);
        FeatureMst::factory()->create(['name' => 'other_feature']);

        $response = $this->call('GET', $this->listUrl, ['name' => 'target'], $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);
        $items = $response->json('data.data');

        $this->assertTrue(collect($items)->contains('id', $target->id));
        $this->assertFalse(collect($items)->contains('name', 'other_feature'));
    }

    public function test_ft_r_ls_t_005_filter_by_status_invalid()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->listUrl, ['status' => 100], $cookies);

        $this->assertCustomValidationErrors($response, ['status']);
    }

    public function test_ft_r_ls_t_006_valid_status_filter()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $active = FeatureMst::factory()->create(['status' => StatusEnum::PUBLISHED->value]);
        $inactive = FeatureMst::factory()->create(['status' => StatusEnum::DRAFT->value]);

        $response = $this->call('GET', $this->listUrl, ['status' => StatusEnum::PUBLISHED->value], $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);
        $items = $response->json('data.data');

        $this->assertTrue(collect($items)->contains('id', $active->id));
        $this->assertFalse(collect($items)->contains('id', $inactive->id));
    }

    public function test_ft_r_ls_t_007_success_no_filter()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->listUrl, [], $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);
        $response->assertJsonStructure(['data']);
    }
}
