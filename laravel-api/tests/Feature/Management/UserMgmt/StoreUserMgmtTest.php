<?php

namespace Tests\Feature\Management\UserMgmt;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class StoreUserMgmtTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/user-mgmt/store';

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

  public function test_USER_STO_R001_wrong_http_method()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl, [], $cookies);
    $response->assertStatus(405);
  }

  // ========== MIDDLEWARE LAYER TESTS ==========

  public function test_USER_STO_M001_unauthenticated()
  {
    $response = $this->postJson($this->baseUrl, []);
    $response->assertStatus(401);
  }

  // ========== VALIDATION LAYER TESTS - REQUIRED FIELDS ==========

  public function test_USER_STO_V001_required_fields_missing()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertStatus(422);
    // // $response->assertJsonValidationErrors(['user_name', 'email', 'password', 'first_name', 'last_name']);
  }

  // ========== VALIDATION LAYER TESTS - EMAIL ==========

  public function test_USER_STO_V002_email_invalid_format()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = self::getValidPayload(['email' => 'invalid-email']);

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
    // $response->assertJsonValidationErrors(['email']);
  }

  public function test_USER_STO_V003_email_exceeds_max_length()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = self::getValidPayload(['email' => str_repeat('a', 256) . '@example.com']);

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(422);
  }

  // ========== SERVICE LAYER TESTS ==========

  public function test_USER_STO_S001_success()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = self::getValidPayload(['email' => 'testuser@gmail.com']);

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    if ($response->status() !== 200) {
      dump($response->json());
    }
    $response->assertStatus(200);

    $id = $response->json('data');
    $this->assertDatabaseHas('user_mgmt', [
      'id' => $id,
      'email' => $payload['email'],
      'user_name' => $payload['user_name'],
    ]);

    $this->assertDatabaseHas('user_mgmt_hist', [
      'user_mgmt_id' => $id,
      'action' => 1, // CREATE
    ]);
  }

  private static function getValidPayload(array $overrides = []): array
  {
    return array_merge([
      'user_name' => 'testuser',
      'email' => 'testuser@example.com',
      'password' => 'password123',
      'first_name' => 'Test',
      'last_name' => 'User',
      'address' => '123 Test St',
      'phone_number' => '1234567890',
      'birth' => '01/01/2000',
      'gender' => 1,
      'status' => 1,
      'is_active' => 1,
      'is_delete' => 0,
    ], $overrides);
  }
}
