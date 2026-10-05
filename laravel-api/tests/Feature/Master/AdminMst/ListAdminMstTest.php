<?php

namespace Tests\Feature\Master\AdminMst;

use App\Constants\CommonVal;
use App\Enums\Gender;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use App\Models\Master\AdminMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

class ListAdminMstTest extends TestCase
{
  use DatabaseTransactions;
  use GrantsApiAccess;

  protected string $listUrl = '/api/admin/admin-mst/list';
  protected string $loginUrl = '/api/admin/credential/login';

  protected function setUp(): void
  {
    parent::setUp();
    Redis::flushall();
  }

  /**
   * Helper to get authenticated cookies with 'root' role
   */
  protected function getAuthCookies(AdminMst $admin): array
  {
    $rootRole = RoleMst::where('name', 'root')->first();
    if (!$rootRole) {
      $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
    }
    $this->grantAccessTo($rootRole, 'GET', 'api/admin/admin-mst/list');

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
   * MST_LST_001: Unauthenticated
   */
  public function test_MST_LST_001_unauthenticated()
  {
    $response = $this->getJson($this->listUrl);
    $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
  }

  /**
   * MST_LST_002: Invalid Date Format
   */
  public function test_MST_LST_002_invalid_date_format()
  {
    $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->listUrl, ['from_date' => '2020-01-01'], $cookies);

    $this->assertCustomValidationErrors($response, ['from_date']);
  }

  /**
   * MST_LST_003: To Date Before From Date
   */
  public function test_MST_LST_003_to_date_before_from_date()
  {
    $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
    $cookies = $this->getAuthCookies($admin);

    $params = [
      'from_date' => '02/01/2020',
      'to_date' => '01/01/2020',
    ];

    $response = $this->call('GET', $this->listUrl, $params, $cookies);

    $this->assertCustomValidationErrors($response, ['to_date']);
  }

  /**
   * MST_LST_004: Filter By User Name
   */
  public function test_MST_LST_004_filter_by_user_name()
  {
    $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
    $cookies = $this->getAuthCookies($admin);

    $target = AdminMst::factory()->create(['user_name' => 'target_user']);
    AdminMst::factory()->create(['user_name' => 'other_user']);

    $response = $this->call('GET', $this->listUrl, ['user_name' => 'target'], $cookies);

    $response->assertStatus(CommonVal::HTTP_OK);
    $data = $response->json('data');

    // Assert at least one result and check structure
    // Assuming pagination structure (data, links, meta) or resource collection (data wrapped)
    // If JsonResource collection, usually 'data' is array of items.

    // Response structure: data -> { data: [items], meta: ..., links: ... }
    $items = $response->json('data.data');

    // Filter result check
    $this->assertTrue(collect($items)->contains('id', $target->id));
    $this->assertFalse(collect($items)->contains('user_name', 'other_user'));
  }

  /**
   * MST_LST_005: Filter By Enum (Invalid)
   */
  public function test_MST_LST_005_filter_by_enum_invalid()
  {
    $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->listUrl, ['gender' => 100], $cookies);

    $this->assertCustomValidationErrors($response, ['gender']);
  }

  /**
   * MST_LST_006: Valid Enum Filter
   */
  public function test_MST_LST_006_valid_enum_filter()
  {
    $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
    $cookies = $this->getAuthCookies($admin);

    $male = AdminMst::factory()->create(['gender' => Gender::MALE->value]);
    $female = AdminMst::factory()->create(['gender' => Gender::FEMALE->value]);

    $response = $this->call('GET', $this->listUrl, ['gender' => Gender::MALE->value], $cookies);

    $response->assertStatus(CommonVal::HTTP_OK);
    $items = $response->json('data.data');

    $this->assertTrue(collect($items)->contains('id', $male->id));
    $this->assertFalse(collect($items)->contains('id', $female->id));
  }

  /**
   * MST_LST_007: Success No Filter
   */
  public function test_MST_LST_007_success_no_filter()
  {
    $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('GET', $this->listUrl, [], $cookies);

    $response->assertStatus(CommonVal::HTTP_OK);
    $response->assertJsonStructure(['data']);
  }
}
