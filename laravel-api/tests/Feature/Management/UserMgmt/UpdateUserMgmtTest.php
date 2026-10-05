<?php

namespace Tests\Feature\Management\UserMgmt;

use App\Models\Management\UserMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class UpdateUserMgmtTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/user-mgmt/update';

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

  public function test_USER_UPD_R001_wrong_http_method()
  {
    $admin = AdminMst::factory()->create();
    $user = UserMgmt::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('POST', $this->baseUrl . '/' . $user->id, [], $cookies);
    $response->assertStatus(405);
  }

  public function test_USER_UPD_M001_unauthenticated()
  {
    $user = UserMgmt::factory()->create();
    $response = $this->putJson($this->baseUrl . '/' . $user->id, []);
    $response->assertStatus(401);
  }

  public function test_USER_UPD_V001_required_fields_missing()
  {
    $admin = AdminMst::factory()->create();
    $user = UserMgmt::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = ['id' => $user->id];

    $response = $this->call('PUT', $this->baseUrl . '/' . $user->id, $payload, $cookies);
    $response->assertStatus(422);
    $response->assertStatus(422);
    // User Name and Email required in Update request
    // $response->assertJsonValidationErrors(['user_name', 'email'], 'error.messages');
  }

  public function test_USER_UPD_S001_success()
  {
    $admin = AdminMst::factory()->create();
    $user = UserMgmt::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'id' => $user->id,
      'user_name' => 'updateduser',
      'password' => 'newpassword123',
      'email' => 'testuser_updated@gmail.com',
      'first_name' => 'Updated',
      'last_name' => 'User',
      'status' => 1,
      'is_active' => 1,
      'is_delete' => 0,
      'phone_number' => '9876543210',
      'address' => 'Updated Address',
      'birth' => '01/01/1990',
      'gender' => 1,
    ];

    $response = $this->call('PUT', $this->baseUrl . '/' . $user->id, $payload, $cookies);
    if ($response->status() !== 200) {
      dump($response->json());
    }
    $response->assertStatus(200);

    $this->assertDatabaseHas('user_mgmt', [
      'id' => $user->id,
      'user_name' => 'updateduser',
      'email' => 'testuser_updated@gmail.com',
      'first_name' => 'Updated',
    ]);

    $this->assertDatabaseHas('user_mgmt_hist', [
      'user_mgmt_id' => $user->id,
      'action' => 2, // UPDATE
    ]);
  }
}
