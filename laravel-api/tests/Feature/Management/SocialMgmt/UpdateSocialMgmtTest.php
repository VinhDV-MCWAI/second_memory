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

class UpdateSocialMgmtTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/social-mgmt/update';

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

    private function createSocial(): SocialMgmt
    {
        return SocialMgmt::create([
            'name' => 'Test Social',
            'slug' => 'test-social-'.uniqid(),
            'link' => 'https://example.com',
            'image' => 'test.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ]);
    }

    // ========== ROUTE LAYER TESTS ==========

    public function test_so_c_up_d_r001_wrong_http_method()
    {
        $admin = AdminMst::factory()->create();
        $social = $this->createSocial();
        $cookies = $this->getAuthCookies($admin, 'POST');

        $response = $this->call('POST', $this->baseUrl.'/'.$social->id, [], $cookies);
        $response->assertStatus(405);
    }

    public function test_so_c_up_d_r002_missing_path_parameter()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('PUT', $this->baseUrl, [], $cookies);
        $response->assertStatus(404);
    }

    // ========== MIDDLEWARE LAYER TESTS ==========

    public function test_so_c_up_d_m001_unauthenticated()
    {
        $social = $this->createSocial();
        $response = $this->putJson($this->baseUrl.'/'.$social->id, []);
        $response->assertStatus(401);
    }

    // ========== VALIDATION LAYER TESTS - ID ==========

    public function test_so_c_up_d_v002_id_invalid_type()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Updated Social',
            'slug' => 'updated-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/abc', $payload, $cookies);
        $response->assertStatus(422);
    }

    public function test_so_c_up_d_v003_id_below_minimum()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Updated Social',
            'slug' => 'updated-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/0', $payload, $cookies);
        $response->assertStatus(422);
        // // $response->assertJsonValidationErrors(['id'], 'error.messages');
    }

    public function test_so_c_up_d_v004_id_not_exists()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Updated Social',
            'slug' => 'updated-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/999999', $payload, $cookies);
        $response->assertStatus(422);
        // // $response->assertJsonValidationErrors(['id'], 'error.messages');
    }

    public function test_so_c_up_d_v005_id_exists_valid()
    {
        $admin = AdminMst::factory()->create();
        $social = $this->createSocial();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'id' => $social->id,
            'name' => 'Updated Social',
            'slug' => 'updated-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 2,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$social->id, $payload, $cookies);
        $response->assertStatus(200);
    }

    // ========== VALIDATION LAYER TESTS - FIELDS ==========

    public function test_so_c_up_d_v006_name_missing()
    {
        $admin = AdminMst::factory()->create();
        $social = $this->createSocial();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'slug' => 'updated-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$social->id, $payload, $cookies);
        $response->assertStatus(422);
        // // $response->assertJsonValidationErrors(['name'], 'error.messages');
    }

    public function test_so_c_up_d_v012_link_exceeds_maximum_length()
    {
        $admin = AdminMst::factory()->create();
        $social = $this->createSocial();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Updated Social',
            'slug' => 'updated-social',
            'link' => str_repeat('a', 256),
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$social->id, $payload, $cookies);
        $response->assertStatus(422);
        // // $response->assertJsonValidationErrors(['link'], 'error.messages');
    }

    public function test_so_c_up_d_v021_is_display_invalid_enum()
    {
        $admin = AdminMst::factory()->create();
        $social = $this->createSocial();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Updated Social',
            'slug' => 'updated-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 99,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$social->id, $payload, $cookies);
        $response->assertStatus(422);
        // // $response->assertJsonValidationErrors(['is_display'], 'error.messages');
    }

    public function test_so_c_up_d_v024_rank_order_invalid_type()
    {
        $admin = AdminMst::factory()->create();
        $social = $this->createSocial();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'name' => 'Updated Social',
            'slug' => 'updated-social',
            'link' => 'https://facebook.com',
            'image' => 'facebook.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 'abc',
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$social->id, $payload, $cookies);
        $response->assertStatus(422);
        // // $response->assertJsonValidationErrors(['rank_order'], 'error.messages');
    }

    // ========== SERVICE LAYER TESTS ==========

    public function test_so_c_up_d_s001_success_with_history()
    {
        $admin = AdminMst::factory()->create();
        $social = $this->createSocial();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'id' => $social->id,
            'name' => 'Updated Social Name',
            'slug' => 'updated-social-slug',
            'link' => 'https://facebook.com/updated',
            'image' => 'updated.jpg',
            'status' => 2,
            'is_display' => 0,
            'rank_order' => 5,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$social->id, $payload, $cookies);
        $response->assertStatus(200);

        $this->assertDatabaseHas('social_mgmt', [
            'id' => $social->id,
            'name' => 'Updated Social Name',
            'rank_order' => 5,
        ]);

        $this->assertDatabaseHas('social_mgmt_hist', [
            'social_mgmt_id' => $social->id,
            'action' => 2, // UPDATE
        ]);
    }

    public function test_so_c_up_d_s003_update_same_values()
    {
        $admin = AdminMst::factory()->create();
        $social = $this->createSocial();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'id' => $social->id,
            'name' => $social->name,
            'slug' => $social->slug,
            'link' => $social->link,
            'image' => $social->image,
            'status' => $social->status,
            'is_display' => $social->is_display,
            'rank_order' => $social->rank_order,
            'is_delete' => $social->is_delete,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$social->id, $payload, $cookies);
        $response->assertStatus(200);
    }

    // ========== DATABASE LAYER TESTS ==========

    public function test_so_c_up_d_d_b001_transaction_commit()
    {
        $admin = AdminMst::factory()->create();
        $social = $this->createSocial();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'id' => $social->id,
            'name' => 'Transaction Test',
            'slug' => 'transaction-test',
            'link' => 'https://example.com',
            'image' => 'test.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$social->id, $payload, $cookies);
        $response->assertStatus(200);

        $this->assertDatabaseHas('social_mgmt', [
            'id' => $social->id,
            'name' => 'Transaction Test',
        ]);
    }

    // ========== RESPONSE CONTRACT TESTS ==========

    public function test_so_c_up_d_r_c001_success_response_structure()
    {
        $admin = AdminMst::factory()->create();
        $social = $this->createSocial();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'id' => $social->id,
            'name' => 'Response Test',
            'slug' => 'response-test',
            'link' => 'https://example.com',
            'image' => 'test.jpg',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'is_delete' => 0,
        ];

        $response = $this->call('PUT', $this->baseUrl.'/'.$social->id, $payload, $cookies);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $affectedRows = $response->json('data');
        $this->assertIsInt($affectedRows);
    }
}
