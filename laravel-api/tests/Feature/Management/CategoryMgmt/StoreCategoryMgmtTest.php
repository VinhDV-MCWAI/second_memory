<?php

namespace Tests\Feature\Management\CategoryMgmt;

use App\Constants\CommonVal;
use App\Models\Management\CategoryMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Enums\StatusEnum;
use App\Enums\IsActive;
use App\Enums\IsDelete;

class StoreCategoryMgmtTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/category-mgmt/store';

  /**
   * Helper to get authenticated cookies with 'root' role
   */
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

  /**
   * Test [CAT_MEM_STR_001] Unauthenticated
   */
  public function test_CAT_MEM_STR_001_unauthenticated()
  {
    $response = $this->postJson($this->baseUrl, []);
    $response->assertStatus(401);
  }

  /**
   * Test [CAT_MEM_STR_002] Missing Required Fields
   */
  public function test_CAT_MEM_STR_002_missing_required_fields()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $response = $this->call('POST', $this->baseUrl, [], $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('name', $response->json('error.messages'));
    $this->assertArrayHasKey('slug', $response->json('error.messages'));
    $this->assertArrayHasKey('status', $response->json('error.messages'));
    $this->assertArrayHasKey('is_display', $response->json('error.messages'));
    $this->assertArrayHasKey('rank_order', $response->json('error.messages'));
    $this->assertArrayHasKey('is_delete', $response->json('error.messages'));
  }

  /**
   * Test [CAT_MEM_STR_003] Max Length Validation
   */
  public function test_CAT_MEM_STR_003_max_length_validation()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'parent_id' => 0,
      'name' => str_repeat('a', 51),
      'slug' => str_repeat('a', 51),
      'description' => str_repeat('a', 151),
      'status' => StatusEnum::PUBLISHED->value,
      'is_display' => IsActive::TRUE->value,
      'rank_order' => 1,
      'is_delete' => IsDelete::FALSE->value,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('name', $response->json('error.messages'));
    $this->assertArrayHasKey('slug', $response->json('error.messages'));
    $this->assertArrayHasKey('description', $response->json('error.messages'));
  }

  /**
   * Test [CAT_MEM_STR_004] Invalid Enum
   */
  public function test_CAT_MEM_STR_004_invalid_enum()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'parent_id' => 0,
      'name' => 'Valid Name',
      'slug' => 'valid-slug',
      'status' => 999,
      'is_display' => 999,
      'rank_order' => 1,
      'is_delete' => 999,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('status', $response->json('error.messages'));
    $this->assertArrayHasKey('is_display', $response->json('error.messages'));
    $this->assertArrayHasKey('is_delete', $response->json('error.messages'));
  }

  /**
   * Test [CAT_MEM_STR_005] Success
   */
  public function test_CAT_MEM_STR_005_success()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'parent_id' => 0,
      'name' => 'New Category',
      'slug' => 'new-category',
      'description' => 'Category Description',
      'status' => StatusEnum::PUBLISHED->value,
      'is_display' => IsActive::TRUE->value,
      'rank_order' => 1,
      'is_delete' => IsDelete::FALSE->value,
    ];

    $response = $this->call('POST', $this->baseUrl, $payload, $cookies);

    if ($response->status() !== 200) {
      $response->dump();
    }
    $response->assertStatus(200);

    // Verify ID returned
    $id = $response->json();
    $this->assertNotNull($id);

    $this->assertDatabaseHas('category_mgmt', [
      'name' => 'New Category',
      'slug' => 'new-category',
      'is_delete' => 0,
    ]);

    // Verify History
    $this->assertDatabaseHas('category_mgmt_hist', [
      'category_mgmt_id' => $id,
      'name' => 'New Category',
      'action' => 1, // Create
    ]);
  }
}
