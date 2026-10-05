<?php

namespace Tests\Feature\Master\ApiRoleMst;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use App\Models\Master\ApiRoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListApiRoleMstTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/api-role-mst/list';

  /**
   * Helper to get authenticated cookies with 'root' role
   */
  private function getAuthCookies(AdminMst $admin): array
  {
    $rootRole = RoleMst::where('name', 'root')->first();
    if (!$rootRole) {
      $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
    }

    $this->grantAccessTo($rootRole, 'GET', 'api/admin/api-role-mst/list');

    if (!\Illuminate\Support\Facades\DB::table('admin_role_mst')
      ->where('admin_mst_id', $admin->id)
      ->where('role_mst_id', $rootRole->id)
      ->exists()) {
      \Illuminate\Support\Facades\DB::table('admin_role_mst')->insert([
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
        'name' => "Endpoint $method $path",
        'is_active' => 1,
        'feature_mst_id' => $feature->id,
        'is_delete' => 0
      ]
    );

    \Illuminate\Support\Facades\DB::table('api_role_mst')->insertOrIgnore([
      'api_mst_id' => $api->id,
      'role_mst_id' => $role->id,
      'created_at' => now(),
      'updated_at' => now(),
    ]);
  }

  /**
   * Test [MST_AROLE_LST_001] Unauthenticated
   */
  public function test_MST_AROLE_LST_001_unauthenticated()
  {
    $response = $this->getJson($this->baseUrl);
    $response->assertStatus(401);
  }

  /**
   * Test [MST_AROLE_LST_003] Invalid Date Format
   */
  public function test_MST_AROLE_LST_003_invalid_date_format()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'from_date' => 'invalid-date',
    ];

    $response = $this->call('GET', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('from_date', $response->json('error.messages'));
  }

  /**
   * Test [MST_AROLE_LST_004] To Date Before From Date
   */
  public function test_MST_AROLE_LST_004_to_date_before_from_date()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'from_date' => '2023/01/02',
      'to_date' => '2023/01/01',
    ];

    $response = $this->call('GET', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('to_date', $response->json('error.messages'));
  }

  /**
   * Test [MST_AROLE_LST_005] Invalid ID Types
   */
  public function test_MST_AROLE_LST_005_invalid_id_types()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'api_mst_id' => 'abc',
      'role_mst_id' => 'xyz',
    ];

    $response = $this->call('GET', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('api_mst_id', $response->json('error.messages'));
    $this->assertArrayHasKey('role_mst_id', $response->json('error.messages'));
  }

  /**
   * Test [MST_AROLE_LST_006] Success No Filters
   */
  public function test_MST_AROLE_LST_006_success_no_filters()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->baseUrl, [], $cookies);

    if ($response->status() !== 200) {
      $response->dump();
    }
    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => ['data' => []]]);
  }

  /**
   * Test [MST_AROLE_LST_007] Filter by api_mst_id
   */
  public function test_MST_AROLE_LST_007_filter_by_api_mst_id()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $role = RoleMst::factory()->create();
    $feature = FeatureMst::factory()->create();
    $api1 = ApiMst::factory()->create(['feature_mst_id' => $feature->id, 'path' => '/unique/1']);
    $api2 = ApiMst::factory()->create(['feature_mst_id' => $feature->id, 'path' => '/unique/2']);

    ApiRoleMst::create(['api_mst_id' => $api1->id, 'role_mst_id' => $role->id]);
    ApiRoleMst::create(['api_mst_id' => $api2->id, 'role_mst_id' => $role->id]);

    $payload = ['api_mst_id' => $api1->id];
    $response = $this->call('GET', $this->baseUrl, $payload, $cookies);

    $response->assertStatus(200);
    $data = $response->json('data.data');

    // Expect at least 1, as root role might also be assigned
    $this->assertGreaterThanOrEqual(1, count($data));

    $contains = collect($data)->contains(function ($item) use ($api1, $role) {
      return $item['api_mst_id'] == $api1->id && $item['role_mst_id'] == $role->id;
    });
    $this->assertTrue($contains, 'Filtered data should contain the created assignment');
  }

  /**
   * Test [MST_AROLE_LST_008] Filter by role_mst_id
   */
  public function test_MST_AROLE_LST_008_filter_by_role_mst_id()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $role1 = RoleMst::factory()->create();
    $role2 = RoleMst::factory()->create();
    $feature = FeatureMst::factory()->create();
    $api = ApiMst::factory()->create(['feature_mst_id' => $feature->id]);

    ApiRoleMst::create(['api_mst_id' => $api->id, 'role_mst_id' => $role1->id]);
    ApiRoleMst::create(['api_mst_id' => $api->id, 'role_mst_id' => $role2->id]);

    $payload = ['role_mst_id' => $role1->id];
    $response = $this->call('GET', $this->baseUrl, $payload, $cookies);

    $response->assertStatus(200);
    $data = $response->json('data.data');
    $this->assertCount(1, $data);
    $this->assertEquals($role1->id, $data[0]['role_mst_id']);
  }
}
