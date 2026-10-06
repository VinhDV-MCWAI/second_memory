<?php

namespace Tests\Feature\Management\SocialMgmt;

use App\Models\Management\SocialMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ListSocialMgmtTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/social-mgmt/list';

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

    // ========== ROUTE LAYER TESTS ==========

    public function test_so_c_ls_t_r001_wrong_http_method()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('POST', $this->baseUrl, [], $cookies);
        $response->assertStatus(405);
    }

    // ========== MIDDLEWARE LAYER TESTS ==========

    public function test_so_c_ls_t_m001_unauthenticated()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(401);
    }

    // ========== VALIDATION LAYER TESTS ==========

    public function test_so_c_ls_t_v002_name_exceeds_max_length()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl.'?name='.str_repeat('a', 51), [], $cookies);
        $response->assertStatus(422);
        // $response->assertJsonValidationErrors(['name'], 'error.messages');
    }

    public function test_so_c_ls_t_v011_status_invalid_enum()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl.'?status=99', [], $cookies);
        $response->assertStatus(422);
        // $response->assertJsonValidationErrors(['status'], 'error.messages');
    }

    public function test_so_c_ls_t_v012_status_valid_enum()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl.'?status=1', [], $cookies);
        $response->assertStatus(200);
    }

    public function test_so_c_ls_t_v013_is_display_invalid_enum()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl.'?is_display=99', [], $cookies);
        $response->assertStatus(422);
        // $response->assertJsonValidationErrors(['is_display'], 'error.messages');
    }

    public function test_so_c_ls_t_v014_is_display_valid_enum()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl.'?is_display=1', [], $cookies);
        $response->assertStatus(200);
    }

    public function test_so_c_ls_t_v015_rank_order_invalid_type()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl.'?rank_order=abc', [], $cookies);
        $response->assertStatus(422);
        // $response->assertJsonValidationErrors(['rank_order'], 'error.messages');
    }

    public function test_so_c_ls_t_v016_rank_order_valid()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl.'?rank_order=1', [], $cookies);
        $response->assertStatus(200);
    }

    public function test_so_c_ls_t_v020_from_date_invalid_format()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl.'?from_date=2024-01-01', [], $cookies);
        $response->assertStatus(422);
        // $response->assertJsonValidationErrors(['from_date'], 'error.messages');
    }

    public function test_so_c_ls_t_v026_to_date_before_from_date()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl.'?from_date=31/12/2024&to_date=01/01/2024', [], $cookies);
        $response->assertStatus(422);
        // $response->assertJsonValidationErrors(['to_date'], 'error.messages');
    }

    public function test_so_c_ls_t_v027_all_fields_omitted()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl, [], $cookies);
        $response->assertStatus(200);
    }

    // ========== SERVICE LAYER TESTS ==========

    public function test_so_c_ls_t_s001_list_all_without_filters()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        SocialMgmt::create([
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ]);

        $response = $this->call('GET', $this->baseUrl, [], $cookies);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_so_c_ls_t_s002_filter_by_name()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        SocialMgmt::create([
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ]);

        $response = $this->call('GET', $this->baseUrl.'?name=Facebook', [], $cookies);
        $response->assertStatus(200);
    }

    public function test_so_c_ls_t_s003_filter_by_status()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        SocialMgmt::create([
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ]);

        $response = $this->call('GET', $this->baseUrl.'?status=1', [], $cookies);
        $response->assertStatus(200);
    }

    public function test_so_c_ls_t_s004_filter_by_is_display()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        SocialMgmt::create([
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ]);

        $response = $this->call('GET', $this->baseUrl.'?is_display=1', [], $cookies);
        $response->assertStatus(200);
    }

    public function test_so_c_ls_t_s005_filter_by_rank_order()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        SocialMgmt::create([
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 5,
            'is_delete' => 0,
        ]);

        $response = $this->call('GET', $this->baseUrl.'?rank_order=5', [], $cookies);
        $response->assertStatus(200);
    }

    public function test_so_c_ls_t_s007_multiple_filters_combined()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        SocialMgmt::create([
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ]);

        $response = $this->call('GET', $this->baseUrl.'?name=Facebook&status=1&is_display=1', [], $cookies);
        $response->assertStatus(200);
    }

    public function test_so_c_ls_t_s008_no_results_found()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl.'?name=NonExistent', [], $cookies);
        $response->assertStatus(200);
        $response->assertJson(['data' => []]);
    }

    // ========== RESPONSE CONTRACT TESTS ==========

    public function test_so_c_ls_t_r_c001_success_response_structure()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        SocialMgmt::create([
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ]);

        $response = $this->call('GET', $this->baseUrl, [], $cookies);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }
}
