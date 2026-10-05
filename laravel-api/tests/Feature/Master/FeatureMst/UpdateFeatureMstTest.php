<?php

namespace Tests\Feature\Master\FeatureMst;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use App\Enums\StatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class UpdateFeatureMstTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/feature-mst/update';

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

    // Grant PUT access BEFORE login
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

  public function test_FEA_MST_UPD_001_success()
  {
    $feature = FeatureMst::factory()->create();
    $admin = AdminMst::factory()->create();
    // getAuthCookies now handles permissions internally for consistency
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'id' => $feature->id,
      'name' => 'Updated Feature',
      'group_name' => 'Updated Group',
      'description' => 'Updated Desc',
      'status' => StatusEnum::ARCHIVED->value,
      'is_delete' => 0,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $feature->id, $payload, $cookies);
    $response->assertStatus(200);

    $this->assertDatabaseHas('feature_mst', [
      'id' => $feature->id,
      'name' => 'Updated Feature',
      'status' => 2,
    ]);

    $this->assertDatabaseHas('feature_mst_hist', [
      'feature_mst_id' => $feature->id,
      'action' => 2, // UPDATE
    ]);
  }

  public function test_FEA_MST_UPD_002_validation_error()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'id' => 99999, // Non-existent
      'name' => 'Updated Feature',
      'group_name' => 'Updated Group',
      'description' => 'Updated Desc',
      'status' => StatusEnum::ARCHIVED->value,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/99999', $payload, $cookies);
    $response->assertStatus(422); // Exists validation rule should fail
  }
}
