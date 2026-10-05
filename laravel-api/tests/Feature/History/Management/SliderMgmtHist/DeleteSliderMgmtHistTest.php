<?php

namespace Tests\Feature\History\Management\SliderMgmtHist;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use App\Models\Management\SliderMgmt;
use App\Models\History\Management\SliderMgmtHist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class DeleteSliderMgmtHistTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/slider-mgmt-hist/delete';

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

  public function test_SLD_HST_DEL_001_unauthenticated()
  {
    $response = $this->deleteJson($this->baseUrl . '/1');
    $response->assertStatus(401);
  }

  public function test_SLD_HST_DEL_002_success()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);
    $setting = SliderMgmt::factory()->create();

    $hist = SliderMgmtHist::create([
      'setting_link_mgmt_id' => $setting->id,
      'key' => $setting->key,
      'value' => $setting->value,
      'action' => 1,
      'author_id' => $admin->id,
      'created_at' => now(),
    ]);

    $response = $this->call('DELETE', $this->baseUrl . '/' . $hist->id, ['ids' => [$hist->id]], $cookies);
    $response->assertStatus(200);

    // Check hard delete
    $this->assertDatabaseMissing('setting_link_mgmt_hist', [
      'id' => $hist->id,
    ]);
  }
}
