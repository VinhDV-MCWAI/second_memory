<?php

declare(strict_types=1);

namespace Tests\Feature\Management\SocialMgmt;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class StoreSocialMgmtTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/social-mgmt/store';

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

        $this->grantAccessTo($rootRole, 'POST', $this->baseUrl);

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

    public function test_so_c_st_o_r001_wrong_http_method()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('GET', $this->baseUrl, [], $cookies);
        $response->assertStatus(405);
    }

    // ========== MIDDLEWARE LAYER TESTS ==========

    public function test_so_c_st_o_m001_unauthenticated()
    {
        $response = $this->postJson($this->baseUrl, []);
        $response->assertStatus(401);
    }

    // ========== VALIDATION LAYER TESTS - NAME ==========

    public function test_so_c_st_o_v001_name_missing()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'slug' => 'test-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
        // // // $response->assertJsonValidationErrors(['name'], 'error.messages');
    }

    public function test_so_c_st_o_v002_name_invalid_type()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 123,
            'slug' => 'test-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
        // // // $response->assertJsonValidationErrors(['name'], 'error.messages');
    }

    public function test_so_c_st_o_v004_name_exceeds_maximum_length()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => str_repeat('a', 51),
            'slug' => 'test-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
        // // // $response->assertJsonValidationErrors(['name'], 'error.messages');
    }

    public function test_so_c_st_o_v005_name_boundary_50_characters()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => str_repeat('a', 50),
            'slug' => 'test-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
    }

    // ========== VALIDATION LAYER TESTS - LINK ==========

    public function test_so_c_st_o_v012_link_exceeds_maximum_length()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://example.com/'.str_repeat('a', 256),
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
        // // // $response->assertJsonValidationErrors(['link'], 'error.messages');
    }

    public function test_so_c_st_o_v013_link_boundary_255_characters()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => str_repeat('a', 255),
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
    }

    // ========== VALIDATION LAYER TESTS - STATUS ==========

    public function test_so_c_st_o_v018_status_invalid_enum()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 99,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
        // // // $response->assertJsonValidationErrors(['status'], 'error.messages');
    }

    public function test_so_c_st_o_v019_status_valid_enum_values()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        // Test status=0 (DRAFT)
        $payload = [
            'name' => 'Facebook Draft',
            'slug' => 'facebook-draft',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 0,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];
        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
        $this->assertDatabaseHas('social_mgmt', ['status' => 0]);

        // Test status=1 (PUBLISHED)
        $payload['name'] = 'Facebook Published';
        $payload['slug'] = 'facebook-published';
        $payload['status'] = 1;
        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
        $this->assertDatabaseHas('social_mgmt', ['status' => 1]);

        // Test status=2 (ARCHIVED)
        $payload['name'] = 'Facebook Archived';
        $payload['slug'] = 'facebook-archived';
        $payload['status'] = 2;
        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
        $this->assertDatabaseHas('social_mgmt', ['status' => 2]);
    }

    // ========== VALIDATION LAYER TESTS - IS_DISPLAY ==========

    public function test_so_c_st_o_v020_is_display_missing()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
        // // // $response->assertJsonValidationErrors(['is_display'], 'error.messages');
    }

    public function test_so_c_st_o_v021_is_display_invalid_enum()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 5,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
        // // // $response->assertJsonValidationErrors(['is_display'], 'error.messages');
    }

    public function test_so_c_st_o_v022_is_display_valid_enum_values()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        // Test is_display=0 (FALSE/Hidden)
        $payload = [
            'name' => 'Facebook Hidden',
            'slug' => 'facebook-hidden',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 0,
            'rank_order' => 1,
            'is_delete' => 0,
        ];
        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
        $this->assertDatabaseHas('social_mgmt', ['is_display' => 0]);

        // Test is_display=1 (TRUE/Visible)
        $payload['name'] = 'Facebook Visible';
        $payload['slug'] = 'facebook-visible';
        $payload['is_display'] = 1;
        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
        $this->assertDatabaseHas('social_mgmt', ['is_display' => 1]);
    }

    // ========== VALIDATION LAYER TESTS - RANK_ORDER ==========

    public function test_so_c_st_o_v023_rank_order_missing()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
        // // // $response->assertJsonValidationErrors(['rank_order'], 'error.messages');
    }

    public function test_so_c_st_o_v024_rank_order_invalid_type()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 'abc',
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
        // // // $response->assertJsonValidationErrors(['rank_order'], 'error.messages');
    }

    public function test_so_c_st_o_v027_rank_order_valid_value()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 5,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
        $this->assertDatabaseHas('social_mgmt', ['rank_order' => 5]);
    }

    // ========== VALIDATION LAYER TESTS - IS_DELETE ==========

    public function test_so_c_st_o_v028_is_delete_missing()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(422);
        // // // $response->assertJsonValidationErrors(['is_delete'], 'error.messages');
    }

    public function test_so_c_st_o_v030_is_delete_valid_enum_values()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        // Test is_delete=0
        $payload = [
            'name' => 'Facebook Active',
            'slug' => 'facebook-active',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];
        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
        $this->assertDatabaseHas('social_mgmt', ['is_delete' => 0]);

        // Test is_delete=1
        $payload['name'] = 'Facebook Deleted';
        $payload['slug'] = 'facebook-deleted';
        $payload['is_delete'] = 1;
        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
        $this->assertDatabaseHas('social_mgmt', ['is_delete' => 1]);
    }

    // ========== SERVICE LAYER TESTS ==========

    public function test_so_c_st_o_s001_success_with_history()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        if ($response->status() !== 200) {
            dd($response->content());
        }
        $response->assertStatus(200);

        $this->assertDatabaseHas('social_mgmt', [
            'name' => 'Facebook',
            'slug' => 'facebook',
        ]);

        $id = $response->json('data');
        $this->assertDatabaseHas('social_mgmt_hist', [
            'social_mgmt_id' => $id,
            'action' => 1, // CREATE
        ]);
    }

    // ========== DATABASE LAYER TESTS ==========

    public function test_so_c_st_o_d_b001_transaction_commit()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook-commit',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);

        $id = $response->json('data');

        // Both records should be committed
        $this->assertDatabaseHas('social_mgmt', ['id' => $id]);
        $this->assertDatabaseHas('social_mgmt_hist', ['social_mgmt_id' => $id]);
    }

    // ========== RESPONSE CONTRACT TESTS ==========

    public function test_so_c_st_o_r_c001_success_response_structure()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Facebook',
            'slug' => 'facebook-response',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $id = $response->json('data');
        $this->assertIsInt($id);
    }
}
