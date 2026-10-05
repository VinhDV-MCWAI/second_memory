<?php

namespace Tests\Feature\Management\SettingLinkMgmt;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use App\Models\Management\SettingLinkMgmt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class UpdateSettingLinkMgmtTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/setting-link-mgmt/update';

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

    $this->grantAccessTo($rootRole, 'PUT', $this->baseUrl . '/{id}');

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

  public function test_SET_LNK_UPD_001_unauthenticated()
  {
    $response = $this->putJson($this->baseUrl . '/1', []);
    $response->assertStatus(401);
  }

  public function test_SET_LNK_UPD_002_validation_errors()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);
    $setting = SettingLinkMgmt::factory()->create();

    $response = $this->call('PUT', $this->baseUrl . '/' . $setting->id, [], $cookies);
    $response->assertStatus(422);
  }

  public function test_SET_LNK_UPD_003_success()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);
    $setting = SettingLinkMgmt::factory()->create(['key' => 'old_key']);

    $payload = [
      'id' => $setting->id,
      'key' => 'updated_key',
      'value' => 'https://updated.com',
      'is_delete' => 0,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $setting->id, $payload, $cookies);
    $response->assertStatus(200);

    $this->assertDatabaseHas('setting_link_mgmt', [
      'id' => $setting->id,
      'key' => 'updated_key',
    ]);

    $this->assertDatabaseHas('setting_link_mgmt_hist', [
      'setting_link_mgmt_id' => $setting->id,
      'action' => 2,
    ]);
  }
}
