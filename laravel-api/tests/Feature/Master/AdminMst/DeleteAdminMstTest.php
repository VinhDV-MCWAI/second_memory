<?php

namespace Tests\Feature\Master\AdminMst;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use App\Models\Master\RoleMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\ApiMst;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class DeleteAdminMstTest extends TestCase
{
  use DatabaseTransactions;

  protected string $loginUrl = '/api/admin/credential/login';
  protected string $deleteUrl = '/api/admin/admin-mst/delete';

  protected function setUp(): void
  {
    parent::setUp();
    Redis::flushall();
  }

  protected function getAuthCookies(AdminMst $admin): array
  {
    $rootRole = RoleMst::where('name', 'root')->first();
    if (!$rootRole) {
      $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'status' => 1, 'is_active' => 1, 'is_delete' => 0]);
    }

    // Grant access to POST .../delete
    $this->grantAccessTo($rootRole, 'POST', ltrim($this->deleteUrl, '/'));

    if (!DB::table('admin_role_mst')->where('admin_mst_id', $admin->id)->where('role_mst_id', $rootRole->id)->exists()) {
      DB::table('admin_role_mst')->insert([
        'admin_mst_id' => $admin->id,
        'role_mst_id' => $rootRole->id,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    $response = $this->postJson($this->loginUrl, [
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

  /**
   * Test delete single admin success via POST batch route
   */
  public function test_delete_single_success()
  {
    $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
    $cookies = $this->getAuthCookies($admin);

    $target = AdminMst::factory()->create();

    // Use call() to ensure cookies are passed correctly
    $response = $this->call('POST', $this->deleteUrl, ['ids' => [$target->id]], $cookies);

    $response->assertStatus(CommonVal::HTTP_OK);

    $this->assertDatabaseHas('admin_mst', [
      'id' => $target->id,
      'is_delete' => 1
    ]);
  }

  /**
   * Test delete multiple admins success
   */
  public function test_delete_multiple_success()
  {
    $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
    $cookies = $this->getAuthCookies($admin);

    $target1 = AdminMst::factory()->create();
    $target2 = AdminMst::factory()->create();

    $response = $this->call('POST', $this->deleteUrl, ['ids' => [$target1->id, $target2->id]], $cookies);

    $response->assertStatus(CommonVal::HTTP_OK);

    $this->assertDatabaseHas('admin_mst', [
      'id' => $target1->id,
      'is_delete' => 1
    ]);
    $this->assertDatabaseHas('admin_mst', [
      'id' => $target2->id,
      'is_delete' => 1
    ]);
  }

  /**
   * Helper to assert custom validation errors
   */
  protected function assertCustomValidationErrors($response, $keys)
  {
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $json = $response->json();
    $this->assertArrayHasKey('error', $json);
    $this->assertArrayHasKey('messages', $json['error']);

    foreach ((array)$keys as $key) {
      $this->assertArrayHasKey($key, $json['error']['messages']);
    }
  }

  /**
   * Test missing ids payload
   */
  public function test_missing_ids_payload()
  {
    $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('POST', $this->deleteUrl, [], $cookies);

    $this->assertCustomValidationErrors($response, ['ids']);
  }
}
