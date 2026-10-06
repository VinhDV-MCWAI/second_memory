<?php

namespace Tests\Feature\Master\AdminRoleMst;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use App\Models\Master\RoleMst;
// Assuming model exists for Pivot, or using DB
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

class ListAdminRoleMstTest extends TestCase
{
    use DatabaseTransactions;
    use GrantsApiAccess;

    protected string $listUrl = '/api/admin/admin-role-mst/list';

    protected string $loginUrl = '/api/admin/credential/login';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    protected function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->firstOrCreate(
            ['name' => 'root'],
            ['permission' => '{}', 'is_active' => 1, 'is_delete' => 0]
        );
        $this->grantAccessTo($rootRole, 'GET', 'api/admin/admin-role-mst/list');

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

    protected function assertCustomValidationErrors($response, $keys)
    {
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $json = $response->json();
        $this->assertArrayHasKey('error', $json);
        foreach ((array) $keys as $key) {
            $this->assertArrayHasKey($key, $json['error']['messages']);
        }
    }

    public function test_ms_t_ar_m_ls_t_001_unauthenticated()
    {
        $response = $this->getJson($this->listUrl);
        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_ms_t_ar_m_ls_t_002_invalid_date_format()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->listUrl, ['from_date' => '2020/01/01'], $cookies);
        $this->assertCustomValidationErrors($response, ['from_date']);
    }

    public function test_ms_t_ar_m_ls_t_003_to_date_before_from_date()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->listUrl, ['from_date' => '02/01/2020', 'to_date' => '01/01/2020'], $cookies);
        $this->assertCustomValidationErrors($response, ['to_date']);
    }

    public function test_ms_t_ar_m_ls_t_004_invalid_id_types()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->listUrl, ['admin_mst_id' => 'abc', 'role_mst_id' => 'xyz'], $cookies);
        $this->assertCustomValidationErrors($response, ['admin_mst_id', 'role_mst_id']);
    }

    public function test_ms_t_ar_m_ls_t_006_success_no_filters()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->listUrl, [], $cookies);
        if ($response->status() !== CommonVal::HTTP_OK) {
            $response->dump();
        }
        $response->assertStatus(CommonVal::HTTP_OK);
        $response->assertJsonStructure(['data' => ['data', 'meta']]);
    }

    public function test_ms_t_ar_m_ls_t_007_filter_by_admin_mst_id()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $targetAdmin = AdminMst::factory()->create();
        $role = RoleMst::factory()->create();

        // Create pivot record
        DB::table('admin_role_mst')->insert([
            ['admin_mst_id' => $targetAdmin->id, 'role_mst_id' => $role->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->call('GET', $this->listUrl, ['admin_mst_id' => $targetAdmin->id], $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);
        $items = $response->json('data.data');
        $this->assertTrue(collect($items)->contains('admin_mst_id', $targetAdmin->id));
    }
}
