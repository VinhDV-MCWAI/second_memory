<?php

namespace Tests\Feature\Management\SliderMgmt;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class StoreSliderMgmtTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/slider-mgmt/store';

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

  // ========== ROUTE LAYER TESTS ==========

  public function test_SLD_STO_R001_wrong_http_method()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl, [], $cookies);
    $response->assertStatus(405);
  }

  // ========== MIDDLEWARE LAYER TESTS ==========

  public function test_SLD_STO_M001_unauthenticated()
  {
    $response = $this->postJson($this->baseUrl, []);
    $response->assertStatus(401);
  }

  // ========== REQUEST ENTRY LAYER TESTS ==========

  public function test_SLD_STO_RE001_missing_request_body()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    // RE001
    $response = $this->call('POST', $this->baseUrl, [], $cookies);
    $response->assertStatus(422);
  }

  public function test_SLD_STO_RE002_empty_json_payload()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('POST', $this->baseUrl, [], $cookies);
    $response->assertStatus(422);
  }

  // ========== VALIDATION LAYER TESTS - TITLE ==========

  public function test_SLD_STO_V001_title_missing()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['title'], 'error.messages');
  }

  public function test_SLD_STO_V002_title_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 123,
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['title'], 'error.messages');
  }

  public function test_SLD_STO_V003_title_below_minimum_length()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => '',
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['title'], 'error.messages');
  }

  public function test_SLD_STO_V004_title_exceeds_maximum_length()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => str_repeat('a', 51),
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['title'], 'error.messages');
  }

  public function test_SLD_STO_V005_title_boundary_50_characters()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => str_repeat('a', 50),
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
  }

  // ========== VALIDATION LAYER TESTS - SLUG ==========

  public function test_SLD_STO_V006_slug_missing()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['slug'], 'error.messages');
  }

  public function test_SLD_STO_V007_slug_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 123,
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['slug'], 'error.messages');
  }

  public function test_SLD_STO_V008_slug_exceeds_maximum_length()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => str_repeat('a', 51),
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['slug'], 'error.messages');
  }

  // ========== VALIDATION LAYER TESTS - LINK ==========

  public function test_SLD_STO_V009_link_missing()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['link'], 'error.messages');
  }

  public function test_SLD_STO_V010_link_exceeds_maximum_length()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
      'link' => 'https://example.com/' . str_repeat('a', 100),
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['link'], 'error.messages');
  }

  public function test_SLD_STO_V011_link_boundary_100_characters()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
      'link' => str_repeat('a', 100),
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
  }

  // ========== VALIDATION LAYER TESTS - IMAGE ==========

  public function test_SLD_STO_V012_image_missing()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['image'], 'error.messages');
  }

  public function test_SLD_STO_V013_image_exceeds_maximum_length()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => str_repeat('a', 101) . '.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['image'], 'error.messages');
  }

  // ========== VALIDATION LAYER TESTS - STATUS ==========

  public function test_SLD_STO_V014_status_missing()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['status'], 'error.messages');
  }

  public function test_SLD_STO_V015_status_invalid_enum()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 99,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['status'], 'error.messages');
  }

  public function test_SLD_STO_V016_status_valid_enum_draft()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider-draft',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 0,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
    $this->assertDatabaseHas('slider_mgmt', ['status' => 0]);
  }

  public function test_SLD_STO_V016_status_valid_enum_published()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider-published',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
    $this->assertDatabaseHas('slider_mgmt', ['status' => 1]);
  }

  public function test_SLD_STO_V016_status_valid_enum_archived()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider-archived',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 2,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
    $this->assertDatabaseHas('slider_mgmt', ['status' => 2]);
  }

  // ========== VALIDATION LAYER TESTS - IS_DELETE ==========

  public function test_SLD_STO_V017_is_delete_missing()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['is_delete'], 'error.messages');
  }

  public function test_SLD_STO_V018_is_delete_invalid_enum()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 5,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['is_delete'], 'error.messages');
  }

  public function test_SLD_STO_V019_is_delete_valid_enum_false()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider-not-deleted',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
    $this->assertDatabaseHas('slider_mgmt', ['is_delete' => 0]);
  }

  public function test_SLD_STO_V019_is_delete_valid_enum_true()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider-deleted',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 1,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
    $this->assertDatabaseHas('slider_mgmt', ['is_delete' => 1]);
  }

  // ========== SERVICE LAYER TESTS ==========

  public function test_SLD_STO_S001_success_with_history()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $this->assertDatabaseHas('slider_mgmt', [
      'title' => 'Test Slider',
      'slug' => 'test-slider',
    ]);

    $id = $response->json('data');
    $this->assertDatabaseHas('slider_mgmt_hist', [
      'slider_mgmt_id' => $id,
      'action' => 1, // CREATE
    ]);
  }

  public function test_SLD_STO_S002_history_record_with_correct_action()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider-action',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $historyRecord = DB::table('slider_mgmt_hist')
      ->where('slider_mgmt_id', $id)
      ->first();

    $this->assertNotNull($historyRecord);
    $this->assertEquals(1, $historyRecord->action); // ActionType::CREATE
  }

  // ========== DATABASE LAYER TESTS ==========

  public function test_SLD_STO_DB001_transaction_commit()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider-commit',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');

    // Both records should be committed
    $this->assertDatabaseHas('slider_mgmt', ['id' => $id]);
    $this->assertDatabaseHas('slider_mgmt_hist', ['slider_mgmt_id' => $id]);
  }

  public function test_SLD_STO_DB002_transaction_rollback_on_validation_failure()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'slug' => 'test-slider',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);

    // No records should be created
    $this->assertDatabaseMissing('slider_mgmt', ['slug' => 'test-slider']);
  }

  // ========== RESPONSE CONTRACT TESTS ==========

  public function test_SLD_STO_RC001_success_response_structure()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'title' => 'Test Slider',
      'slug' => 'test-slider-response',
      'link' => 'https://example.com',
      'image' => 'test.jpg',
      'status' => 1,
      'is_delete' => 0,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
    $response->assertJsonStructure(['data']);

    $id = $response->json('data');
    $this->assertIsInt($id);
  }
}
