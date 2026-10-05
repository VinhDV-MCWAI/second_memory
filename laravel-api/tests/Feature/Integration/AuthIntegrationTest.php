<?php

namespace Tests\Feature\Integration;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class AuthIntegrationTest extends TestCase
{
  use RefreshDatabase;

  private string $loginUrl = '/api/admin/credential/login';

  protected function setUp(): void
  {
    parent::setUp();
    Redis::flushdb();
    if (!RoleMst::where('name', 'root')->exists()) {
      RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
    }
  }

  private function ensureRootAccess(AdminMst $admin, string $method, string $path)
  {
    $role = RoleMst::where('name', 'root')->first();

    if (!DB::table('admin_role_mst')->where('admin_mst_id', $admin->id)->exists()) {
      DB::table('admin_role_mst')->insert([
        'admin_mst_id' => $admin->id,
        'role_mst_id' => $role->id,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    $feature = FeatureMst::firstOrCreate(['name' => 'System'], [
      'name' => 'System',
      'group_name' => 'System',
      'status' => 1,
      'is_delete' => 0
    ]);

    $typeMap = ['GET' => 0, 'POST' => 1, 'PUT' => 2, 'DELETE' => 4];
    $type = $typeMap[strtoupper($method)] ?? 0;

    $api = ApiMst::firstOrCreate(
      ['path' => $path, 'type' => $type],
      [
        'name' => "API $method $path",
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

  public function test_auth_rbac_lifecycle()
  {
    // 1. Setup Root Admin & Permissions
    $rootParams = ['user_name' => 'root_integration'];
    $rootAdmin = AdminMst::factory()->create($rootParams);

    // Pre-grant ALL necessary permissions for Root Flow
    $this->ensureRootAccess($rootAdmin, 'POST', $this->loginUrl);
    $this->ensureRootAccess($rootAdmin, 'POST', 'api/admin/role-mst/store');
    $this->ensureRootAccess($rootAdmin, 'POST', 'api/admin/admin-mst/store');

    // NOW Login to populate Redis with ALL perms
    $response = $this->postJson($this->loginUrl, [
      'user_name' => $rootAdmin->user_name,
      'password' => 'password',
    ]);
    $rootCookies = [];
    foreach ($response->headers->getCookies() as $cookie) {
      $rootCookies[$cookie->getName()] = $cookie->getValue();
    }
    $this->assertNotNull($rootCookies['access_token'] ?? null, 'Root login failed');

    // 2. Define New Access (Role + API) for Sub Admin
    $targetUrl = 'api/admin/token-mst/list';

    $rolePayload = [
      'name' => 'Integration Tester Role',
      'permission' => '{}',
      'is_active' => 1,
      'is_delete' => 0
    ];
    $roleResp = $this->call('POST', 'api/admin/role-mst/store', $rolePayload, $rootCookies);
    $roleResp->assertStatus(200);
  }

  public function test_login_failure_invalid_credentials()
  {
    $admin = AdminMst::factory()->create(['password' => bcrypt('password')]);

    $response = $this->postJson($this->loginUrl, [
      'user_name' => $admin->user_name,
      'password' => 'wrong_password',
    ]);

    // Expect 401 Unauthorized for invalid credentials
    $response->assertStatus(401);
  }

  public function test_access_denied_without_permission()
  {
    $admin = AdminMst::factory()->create();
    // Ensure Login Access ONLY
    $this->ensureRootAccess($admin, 'POST', $this->loginUrl);

    // Login
    $loginResp = $this->postJson($this->loginUrl, [
      'user_name' => $admin->user_name,
      'password' => 'password',
    ]);

    $cookies = [];
    foreach ($loginResp->headers->getCookies() as $cookie) {
      $cookies[$cookie->getName()] = $cookie->getValue();
    }

    // Try to access endpoints NOT granted
    // e.g. 'api/admin/admin-mst/list'
    $response = $this->call('GET', 'api/admin/admin-mst/list', [], $cookies);

    // Expect 401 Unauthorized (Middleware configured to 401)
    $response->assertStatus(401);
  }
}
