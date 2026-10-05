<?php

namespace Tests\Feature\History\Management\SocialMgmtHist;

use App\Models\History\Management\SocialMgmtHist;
use App\Models\Management\SocialMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class StoreSocialMgmtHistTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/social-mgmt-hist/store';

  protected function setUp(): void
  {
    parent::setUp();
    Redis::flushdb();
  }

  private function getAuthCookies(AdminMst $admin): array
  {
    $rootRole = RoleMst::where('name', 'root')->first();
    if (!$rootRole) {
      $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
    }

    $this->grantAccessTo($rootRole, 'POST', $this->baseUrl);

    if (!DB::table('admin_role_mst')
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
      'is_delete' => 0
    ]);

    $api = ApiMst::firstOrCreate(
      ['path' => $path, 'type' => $type],
      [
        'name' => substr("Endp $method $path", 0, 50),
        'is_active' => 1,
        'feature_mst_id' => $feature->id,
        'is_delete' => 0
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
      'slug' => 'test-social-' . uniqid(),
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_display' => 1,
      'rank_order' => 1,
      'is_delete' => 0,
    ]);
  }

  // ========== ROUTE LAYER TESTS ==========

  public function test_SOC_HIST_STO_R001_wrong_http_method()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl, [], $cookies);
    $response->assertStatus(405);
  }

  // ========== MIDDLEWARE LAYER TESTS ==========

  public function test_SOC_HIST_STO_M001_unauthenticated()
  {
    $response = $this->postJson($this->baseUrl, []);
    $response->assertStatus(401);
  }

  // ========== VALIDATION LAYER TESTS - SOCIAL_MGMT_ID ==========

  public function test_SOC_HIST_STO_V001_social_mgmt_id_missing()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['social_mgmt_id'], 'error.messages');
  }

  public function test_SOC_HIST_STO_V002_social_mgmt_id_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => 'abc',
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['social_mgmt_id'], 'error.messages');
  }

  public function test_SOC_HIST_STO_V005_social_mgmt_id_not_exists()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => 999999,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['social_mgmt_id'], 'error.messages');
  }

  public function test_SOC_HIST_STO_V006_social_mgmt_id_exists_valid()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
  }

  // ========== VALIDATION LAYER TESTS - OPTIONAL FIELDS ==========

  public function test_SOC_HIST_STO_V007_name_optional_omitted()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $id,
      'name' => null,
    ]);
  }

  public function test_SOC_HIST_STO_V008_name_invalid_type_when_provided()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'name' => 123,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name'], 'error.messages');
  }

  public function test_SOC_HIST_STO_V021_status_optional_omitted()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $id,
      'status' => null,
    ]);
  }

  public function test_SOC_HIST_STO_V023_status_valid_enum_values()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    // Test status=0
    $payload = [
      'social_mgmt_id' => $social->id,
      'status' => 0,
      'action' => 1,
      'author_id' => 1,
    ];
    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    // Test status=1
    $payload['status'] = 1;
    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    // Test status=2
    $payload['status'] = 2;
    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
  }

  public function test_SOC_HIST_STO_V024_is_display_optional_omitted()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $id,
      'is_display' => null,
    ]);
  }

  public function test_SOC_HIST_STO_V025_is_display_valid_enum_values()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    // Test is_display=0
    $payload = [
      'social_mgmt_id' => $social->id,
      'is_display' => 0,
      'action' => 1,
      'author_id' => 1,
    ];
    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    // Test is_display=1
    $payload['is_display'] = 1;
    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
  }

  public function test_SOC_HIST_STO_V026_rank_order_optional_omitted()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $id,
      'rank_order' => null,
    ]);
  }

  public function test_SOC_HIST_STO_V027_rank_order_valid_when_provided()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'rank_order' => 5,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $id,
      'rank_order' => 5,
    ]);
  }

  // ========== VALIDATION LAYER TESTS - ACTION ==========

  public function test_SOC_HIST_STO_V028_action_missing()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['action'], 'error.messages');
  }

  public function test_SOC_HIST_STO_V029_action_valid_values()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    // Test action=1 (CREATE)
    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];
    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    // Test action=2 (UPDATE)
    $payload['action'] = 2;
    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    // Test action=3 (DELETE)
    $payload['action'] = 3;
    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
  }

  // ========== VALIDATION LAYER TESTS - AUTHOR_ID ==========

  public function test_SOC_HIST_STO_V030_author_id_missing()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['author_id'], 'error.messages');
  }

  public function test_SOC_HIST_STO_V031_author_id_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 'abc',
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['author_id'], 'error.messages');
  }

  public function test_SOC_HIST_STO_V034_author_id_valid()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
  }

  // ========== SERVICE LAYER TESTS ==========

  public function test_SOC_HIST_STO_S001_success()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $id,
      'social_mgmt_id' => $social->id,
    ]);
  }

  public function test_SOC_HIST_STO_S002_create_with_all_fields()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'name' => 'History Name',
      'slug' => 'history-slug',
      'link' => 'https://example.com',
      'image' => 'history.jpg',
      'status' => 1,
      'is_display' => 1,
      'rank_order' => 5,
      'action' => 2,
      'author_id' => 2,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $id,
      'name' => 'History Name',
      'rank_order' => 5,
      'author_id' => 2,
    ]);
  }

  public function test_SOC_HIST_STO_S003_create_with_only_required()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $id,
      'name' => null,
      'slug' => null,
      'status' => null,
      'is_display' => null,
      'rank_order' => null,
    ]);
  }

  // ========== DATABASE LAYER TESTS ==========

  public function test_SOC_HIST_STO_DB001_transaction_commit()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('social_mgmt_hist', ['id' => $id]);
  }

  // ========== RESPONSE CONTRACT TESTS ==========

  public function test_SOC_HIST_STO_RC001_success_response_structure()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
    $response->assertJsonStructure(['data']);

    $id = $response->json('data');
    $this->assertIsInt($id);
  }
}
