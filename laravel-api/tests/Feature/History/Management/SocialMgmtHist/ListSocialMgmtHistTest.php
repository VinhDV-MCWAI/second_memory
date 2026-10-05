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

class ListSocialMgmtHistTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/social-mgmt-hist/list';

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

    $this->grantAccessTo($rootRole, 'GET', $this->baseUrl);

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

  // ========== ROUTE LAYER TESTS ==========

  public function test_SOC_HIST_LST_R001_wrong_http_method()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('POST', $this->baseUrl, [], $cookies);
    $response->assertStatus(405);
  }

  // ========== MIDDLEWARE LAYER TESTS ==========

  public function test_SOC_HIST_LST_M001_unauthenticated()
  {
    $response = $this->getJson($this->baseUrl);
    $response->assertStatus(401);
  }

  // ========== VALIDATION LAYER TESTS - SLIDER_MGMT_ID ==========

  public function test_SOC_HIST_LST_V001_social_mgmt_id_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl . '?social_mgmt_id=abc', [], $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['social_mgmt_id']);
  }

  public function test_SOC_HIST_LST_V002_social_mgmt_id_below_minimum()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl . '?social_mgmt_id=-1', [], $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['social_mgmt_id']);
  }

  public function test_SOC_HIST_LST_V004_social_mgmt_id_valid()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl . '?social_mgmt_id=1', [], $cookies);
    $response->assertStatus(200);
  }

  // ========== VALIDATION LAYER TESTS - AUTHOR_ID ==========

  public function test_SOC_HIST_LST_V022_author_id_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl . '?author_id=abc', [], $cookies);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['author_id']);
  }

  public function test_SOC_HIST_LST_V023_author_id_valid()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl . '?author_id=1', [], $cookies);
    $response->assertStatus(200);
  }

  // ========== SERVICE LAYER TESTS ==========

  public function test_SOC_HIST_LST_S001_list_all_without_filters()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $social = $this->createSocial();

    SocialMgmtHist::create([
      'social_mgmt_id' => $social->id,
      'title' => 'History 1',
      'action' => 1,
      'author_id' => 1,
    ]);

    SocialMgmtHist::create([
      'social_mgmt_id' => $social->id,
      'title' => 'History 2',
      'action' => 2,
      'author_id' => 1,
    ]);

    $response = $this->call('GET', $this->baseUrl, [], $cookies);
    $response->assertStatus(200);
    $response->assertJsonStructure(['data']);
  }

  public function test_SOC_HIST_LST_S002_filter_by_social_mgmt_id()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $social1 = $this->createSocial();
    $social2 = $this->createSocial();

    SocialMgmtHist::create([
      'social_mgmt_id' => $social1->id,
      'title' => 'History for Social 1',
      'action' => 1,
      'author_id' => 1,
    ]);

    SocialMgmtHist::create([
      'social_mgmt_id' => $social2->id,
      'title' => 'History for Social 2',
      'action' => 1,
      'author_id' => 1,
    ]);

    $response = $this->call('GET', $this->baseUrl . '?social_mgmt_id=' . $social1->id, [], $cookies);
    $response->assertStatus(200);
  }

  public function test_SOC_HIST_LST_S003_filter_by_action()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $social = $this->createSocial();

    SocialMgmtHist::create([
      'social_mgmt_id' => $social->id,
      'title' => 'Create Action',
      'action' => 1,
      'author_id' => 1,
    ]);

    SocialMgmtHist::create([
      'social_mgmt_id' => $social->id,
      'title' => 'Update Action',
      'action' => 2,
      'author_id' => 1,
    ]);

    $response = $this->call('GET', $this->baseUrl . '?action=1', [], $cookies);
    $response->assertStatus(200);
  }

  public function test_SOC_HIST_LST_S004_filter_by_author_id()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $social = $this->createSocial();

    SocialMgmtHist::create([
      'social_mgmt_id' => $social->id,
      'title' => 'History by Author 1',
      'action' => 1,
      'author_id' => 1,
    ]);

    SocialMgmtHist::create([
      'social_mgmt_id' => $social->id,
      'title' => 'History by Author 2',
      'action' => 1,
      'author_id' => 2,
    ]);

    $response = $this->call('GET', $this->baseUrl . '?author_id=1', [], $cookies);
    $response->assertStatus(200);
  }

  public function test_SOC_HIST_LST_S006_multiple_filters_combined()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $social = $this->createSocial();

    SocialMgmtHist::create([
      'social_mgmt_id' => $social->id,
      'title' => 'Matching History',
      'action' => 2,
      'author_id' => 1,
    ]);

    $response = $this->call('GET', $this->baseUrl . '?social_mgmt_id=' . $social->id . '&action=2&author_id=1', [], $cookies);
    $response->assertStatus(200);
  }

  public function test_SOC_HIST_LST_S007_no_results_found()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl . '?social_mgmt_id=999999', [], $cookies);
    $response->assertStatus(200);
    $response->assertJson(['data' => []]);
  }

  // ========== RESPONSE CONTRACT TESTS ==========

  public function test_SOC_HIST_LST_RC001_success_response_structure()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $social = $this->createSocial();

    SocialMgmtHist::create([
      'social_mgmt_id' => $social->id,
      'title' => 'Test History',
      'slug' => 'test-history',
      'action' => 1,
      'author_id' => 1,
    ]);

    $response = $this->call('GET', $this->baseUrl, [], $cookies);
    $response->assertStatus(200);
    $response->assertJsonStructure([
      'data' => [
        '*' => [
          'id',
          'social_mgmt_id',
          'title',
          'slug',
          'link',
          'image',
          'status',
          'action',
          'author_id',
          'created_at',
          'updated_at',
        ]
      ]
    ]);
  }
}
