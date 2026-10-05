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

class UpdateSocialMgmtHistTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/social-mgmt-hist/update';

  protected function setUp(): void
  {
    parent::setUp();
    Redis::flushdb();
  }

  private function getAuthCookies(AdminMst $admin, string $method = 'PUT'): array
  {
    $rootRole = RoleMst::where('name', 'root')->first();
    if (!$rootRole) {
      $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
    }

    $this->grantAccessTo($rootRole, $method, $this->baseUrl . '/{id}');

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
      'title' => 'Test Social',
      'slug' => 'test-social-' . uniqid(),
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ]);
  }

  private function createHistory(SocialMgmt $social): SocialMgmtHist
  {
    return SocialMgmtHist::create([
      'social_mgmt_id' => $social->id,
      'title' => 'Original History Title',
      'slug' => 'original-history-slug',
      'link' => 'https://original.com',
      'image' => 'original.jpg',
      'status' => 1,
      'action' => 1,
      'author_id' => 1,
    ]);
  }

  // ========== ROUTE LAYER TESTS ==========

  public function test_SOC_HIST_UPD_R001_wrong_http_method()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin, 'POST');

    $response = $this->call('POST', $this->baseUrl . '/' . $history->id, [], $cookies);
    $response->assertStatus(405);
  }

  public function test_SOC_HIST_UPD_R002_missing_path_parameter()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('PUT', $this->baseUrl, [], $cookies);
    $response->assertStatus(404);
  }

  // ========== MIDDLEWARE LAYER TESTS ==========

  public function test_SOC_HIST_UPD_M001_unauthenticated()
  {
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $response = $this->putJson($this->baseUrl . '/' . $history->id, []);
    $response->assertStatus(401);
  }

  // ========== VALIDATION LAYER TESTS - ID ==========

  public function test_SOC_HIST_UPD_V002_id_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 2,
      'author_id' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/abc', $payload, $cookies);
    $response->assertStatus(422);
  }

  public function test_SOC_HIST_UPD_V003_id_below_minimum()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 2,
      'author_id' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/0', $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['id']);
  }

  public function test_SOC_HIST_UPD_V004_id_not_exists()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'action' => 2,
      'author_id' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/999999', $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['id']);
  }

  public function test_SOC_HIST_UPD_V005_id_exists_valid()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'title' => 'Updated History Title',
      'action' => 2,
      'author_id' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(200);
  }

  // ========== VALIDATION LAYER TESTS - SLIDER_MGMT_ID ==========

  public function test_SOC_HIST_UPD_V006_social_mgmt_id_missing()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'action' => 2,
      'author_id' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['social_mgmt_id']);
  }

  public function test_SOC_HIST_UPD_V007_social_mgmt_id_not_exists()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => 999999,
      'action' => 2,
      'author_id' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['social_mgmt_id']);
  }

  // ========== SERVICE LAYER TESTS ==========

  public function test_SOC_HIST_UPD_S001_success()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'title' => 'Updated History Title',
      'slug' => 'updated-history-slug',
      'action' => 2,
      'author_id' => 2,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(200);

    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $history->id,
      'title' => 'Updated History Title',
      'slug' => 'updated-history-slug',
      'author_id' => 2,
    ]);
  }

  public function test_SOC_HIST_UPD_S002_update_same_values()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'title' => 'Original History Title',
      'slug' => 'original-history-slug',
      'action' => 1,
      'author_id' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(200);

    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $history->id,
      'title' => 'Original History Title',
    ]);
  }

  // ========== DATABASE LAYER TESTS ==========

  public function test_SOC_HIST_UPD_DB001_transaction_commit()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'title' => 'Updated Title',
      'action' => 2,
      'author_id' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(200);

    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $history->id,
      'title' => 'Updated Title',
    ]);
  }

  public function test_SOC_HIST_UPD_DB002_transaction_rollback_on_validation_failure()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'action' => 2,
      'author_id' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(422);

    // Original record should remain unchanged
    $this->assertDatabaseHas('social_mgmt_hist', [
      'id' => $history->id,
      'title' => 'Original History Title',
    ]);
  }

  // ========== RESPONSE CONTRACT TESTS ==========

  public function test_SOC_HIST_UPD_RC001_success_response_structure()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'social_mgmt_id' => $social->id,
      'title' => 'Updated Title',
      'action' => 2,
      'author_id' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(200);
    $response->assertJsonStructure(['data']);

    $affectedRows = $response->json('data');
    $this->assertIsInt($affectedRows);
  }
}
