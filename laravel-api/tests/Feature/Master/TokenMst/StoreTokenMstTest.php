<?php

namespace Tests\Feature\Master\TokenMst;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use App\Models\Master\TokenMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class StoreTokenMstTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/token-mst/store';

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

  public function test_TOK_STO_R001_wrong_http_method()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl, [], $cookies);
    $response->assertStatus(405);
  }

  // ========== MIDDLEWARE LAYER TESTS ==========

  public function test_TOK_STO_M001_unauthenticated()
  {
    $response = $this->postJson($this->baseUrl, []);
    $response->assertStatus(401);
  }

  // ========== VALIDATION LAYER TESTS ==========

  public function test_TOK_STO_V001_account_id_missing()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = ['token_hash' => 'hash123'];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['account_id'], 'error.messages');
  }

  public function test_TOK_STO_V002_account_id_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = ['account_id' => 'abc', 'token_hash' => 'hash123'];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['account_id'], 'error.messages');
  }

  public function test_TOK_STO_V005_account_id_valid()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = ['account_id' => 1, 'token_hash' => 'hash123'];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
  }

  public function test_TOK_STO_V006_device_name_optional_omitted()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = ['account_id' => 1, 'token_hash' => 'hash123'];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('token_mst', [
      'id' => $id,
      'device_name' => null,
    ]);
  }

  public function test_TOK_STO_V007_device_name_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'account_id' => 1,
      'device_name' => 123,
      'token_hash' => 'hash123',
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['device_name'], 'error.messages');
  }

  public function test_TOK_STO_V008_device_name_exceeds_max_length()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'account_id' => 1,
      'device_name' => str_repeat('a', 256),
      'token_hash' => 'hash123',
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['device_name'], 'error.messages');
  }

  public function test_TOK_STO_V010_ip_address_optional_omitted()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = ['account_id' => 1, 'token_hash' => 'hash123'];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('token_mst', [
      'id' => $id,
      'ip_address' => null,
    ]);
  }

  public function test_TOK_STO_V011_ip_address_invalid_type()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'account_id' => 1,
      'ip_address' => 123,
      'token_hash' => 'hash123',
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['ip_address'], 'error.messages');
  }

  public function test_TOK_STO_V014_expired_at_optional_omitted()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = ['account_id' => 1, 'token_hash' => 'hash123'];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('token_mst', [
      'id' => $id,
      'expired_at' => null,
    ]);
  }

  // ========== SERVICE / DB / RESPONSE TESTS ==========

  public function test_TOK_STO_S001_success_with_all_fields()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'account_id' => 1,
      'device_name' => 'iPhone 15',
      'ip_address' => '192.168.1.100',
      'expired_at' => '31/12/2024',
      'token_hash' => 'hash123',
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    if ($response->status() !== 200) {
      dump($response->content());
    }
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('token_mst', [
      'id' => $id,
      'account_id' => 1,
      'device_name' => 'iPhone 15',
      'ip_address' => '192.168.1.100',
    ]);
  }

  public function test_TOK_STO_RC001_success_response_structure()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = ['account_id' => 1, 'token_hash' => 'hash123'];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(200);
    $response->assertJsonStructure(['data']);

    $id = $response->json('data');
    $this->assertIsInt($id);
  }
}
