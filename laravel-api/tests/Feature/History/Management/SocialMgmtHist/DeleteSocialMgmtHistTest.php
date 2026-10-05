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

class DeleteSocialMgmtHistTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/social-mgmt-hist/delete';

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

    $this->grantAccessTo($rootRole, 'DELETE', $this->baseUrl . '/{id}');

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
      'is_delete' => 0,
      'is_display' => 1,
      'rank_order' => 1,
    ]);
  }

  private function createHistory(SocialMgmt $social): SocialMgmtHist
  {
    return SocialMgmtHist::create([
      'social_mgmt_id' => $social->id,
      'title' => 'Test History',
      'action' => 1,
      'author_id' => 1,
    ]);
  }

  // ========== ROUTE LAYER TESTS ==========

  public function test_SOC_HIST_DEL_R001_wrong_http_method()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('POST', $this->baseUrl . '/' . $history->id, [], $cookies);
    $response->assertStatus(405);
  }

  public function test_SOC_HIST_DEL_R002_missing_path_parameter()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('DELETE', $this->baseUrl, [], $cookies);
    $response->assertStatus(404);
  }

  // ========== MIDDLEWARE LAYER TESTS ==========

  public function test_SOC_HIST_DEL_M001_unauthenticated()
  {
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $response = $this->deleteJson($this->baseUrl . '/' . $history->id, []);
    $response->assertStatus(401);
  }

  // ========== VALIDATION LAYER TESTS - IDS ==========

  public function test_SOC_HIST_DEL_V001_ids_missing()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history->id, [], $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);
  }

  public function test_SOC_HIST_DEL_V002_ids_not_array()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = ['ids' => 123];

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);
  }

  public function test_SOC_HIST_DEL_V003_ids_empty_array()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = ['ids' => []];

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(422);
  }

  public function test_SOC_HIST_DEL_V004_ids_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = ['ids' => ['abc', 'def']];

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids.0']);
  }

  public function test_SOC_HIST_DEL_V007_ids_not_exists()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = ['ids' => [999999]];

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids.0']);
  }

  public function test_SOC_HIST_DEL_V008_multiple_valid_ids()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $history1 = $this->createHistory($social);
    $history2 = $this->createHistory($social);
    $history3 = $this->createHistory($social);

    $payload = ['ids' => [$history1->id, $history2->id, $history3->id]];

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history1->id, $payload, $cookies);
    $response->assertStatus(200);
  }

  public function test_SOC_HIST_DEL_V009_mix_valid_invalid_ids()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = ['ids' => [$history->id, 999999]];

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids.1']);
  }

  // ========== SERVICE LAYER TESTS ==========

  public function test_SOC_HIST_DEL_S001_delete_single()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = ['ids' => [$history->id]];

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(200);

    $this->assertDatabaseMissing('social_mgmt_hist', ['id' => $history->id]);
  }

  public function test_SOC_HIST_DEL_S002_delete_multiple()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $history1 = $this->createHistory($social);
    $history2 = $this->createHistory($social);
    $history3 = $this->createHistory($social);

    $payload = ['ids' => [$history1->id, $history2->id, $history3->id]];

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history1->id, $payload, $cookies);
    $response->assertStatus(200);

    $this->assertDatabaseMissing('social_mgmt_hist', ['id' => $history1->id]);
    $this->assertDatabaseMissing('social_mgmt_hist', ['id' => $history2->id]);
    $this->assertDatabaseMissing('social_mgmt_hist', ['id' => $history3->id]);
  }

  // ========== DATABASE LAYER TESTS ==========

  public function test_SOC_HIST_DEL_DB001_transaction_commit()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $cookies = $this->getAuthCookies($admin);

    $history1 = $this->createHistory($social);
    $history2 = $this->createHistory($social);

    $payload = ['ids' => [$history1->id, $history2->id]];

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history1->id, $payload, $cookies);
    $response->assertStatus(200);

    // All records deleted
    $this->assertDatabaseMissing('social_mgmt_hist', ['id' => $history1->id]);
    $this->assertDatabaseMissing('social_mgmt_hist', ['id' => $history2->id]);
  }

  // ========== RESPONSE CONTRACT TESTS ==========

  public function test_SOC_HIST_DEL_RC001_success_response_structure()
  {
    $admin = AdminMst::factory()->create();
    $social = $this->createSocial();
    $history = $this->createHistory($social);
    $cookies = $this->getAuthCookies($admin);

    $payload = ['ids' => [$history->id]];

    $response = $this->call('DELETE', $this->baseUrl . '/' . $history->id, $payload, $cookies);
    $response->assertStatus(200);
    $response->assertJsonStructure(['data']);
  }
}
