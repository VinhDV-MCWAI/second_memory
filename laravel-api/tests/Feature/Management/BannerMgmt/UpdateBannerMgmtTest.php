<?php

namespace Tests\Feature\Management\BannerMgmt;

use App\Constants\CommonVal;
use App\Models\Management\BannerMgmt;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Enums\StatusEnum;
use App\Enums\IsDelete;

class UpdateBannerMgmtTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/banner-mgmt/update/';

  /**
   * Helper to get authenticated cookies with 'root' role
   */
  private function getAuthCookies(AdminMst $admin): array
  {
    $rootRole = RoleMst::where('name', 'root')->first();
    if (!$rootRole) {
      $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
    }

    // Grant access to UPDATE endpoint
    $this->grantAccessTo($rootRole, 'PUT', 'api/admin/banner-mgmt/update/{id}');

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
        'name' => "Endpoint $method $path",
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
   * Test [MST_BNR_UPD_001] Unauthenticated
   */
  public function test_MST_BNR_UPD_001_unauthenticated()
  {
    $response = $this->putJson($this->baseUrl . '1', []);
    $response->assertStatus(401);
  }

  /**
   * Test [MST_BNR_UPD_003] Not Found / Invalid ID
   */
  public function test_MST_BNR_UPD_003_not_found()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $payload = [
      'id' => 99999, // Non-existent
      'title' => 'Valid Title',
      'slug' => 'valid-slug',
      'description' => 'Valid Description',
      'link' => 'https://example.com',
      'image' => 'image.jpg',
      'position' => 'top',
      'status' => StatusEnum::PUBLISHED->value,
      'is_delete' => IsDelete::FALSE->value,
    ];

    $response = $this->call('PUT', $this->baseUrl . '99999', $payload, $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('id', $response->json('error.messages'));
  }

  /**
   * Test [MST_BNR_UPD_004] Invalid Data
   */
  public function test_MST_BNR_UPD_004_invalid_data()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);
    $banner = BannerMgmt::factory()->create();

    $payload = [
      'id' => $banner->id,
      'title' => str_repeat('a', 51), // Too long
      'slug' => 'valid-slug',
      'description' => 'Valid Description',
      'link' => 'https://example.com',
      'image' => 'image.jpg',
      'position' => 'top',
      'status' => StatusEnum::PUBLISHED->value,
      'is_delete' => IsDelete::FALSE->value,
    ];

    $response = $this->call('PUT', $this->baseUrl . $banner->id, $payload, $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('title', $response->json('error.messages'));
  }

  /**
   * Test [MST_BNR_UPD_005] Success
   */
  public function test_MST_BNR_UPD_005_success()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);
    $banner = BannerMgmt::factory()->create();

    $payload = [
      'id' => $banner->id,
      'title' => 'Updated Title',
      'slug' => 'updated-slug',
      'description' => 'Updated Description',
      'link' => 'https://updated.com',
      'image' => 'updated.jpg',
      'position' => 'bottom',
      'status' => StatusEnum::PUBLISHED->value,
      'is_delete' => IsDelete::FALSE->value,
    ];

    $response = $this->call('PUT', $this->baseUrl . $banner->id, $payload, $cookies);

    if ($response->status() !== 200) {
      $response->dump();
    }

    $response->assertStatus(200);

    // Controller returns int wrapped in data (standard response)
    $id = $response->json('data');
    $this->assertEquals($banner->id, $id);

    $this->assertDatabaseHas('banner_mgmt', [
      'id' => $banner->id,
      'title' => 'Updated Title',
      'slug' => 'updated-slug',
    ]);

    // Check History
    $this->assertDatabaseHas('banner_mgmt_hist', [
      'banner_mgmt_id' => $banner->id,
      'title' => 'Updated Title',
      'action' => \App\Enums\ActionType::UPDATE->value,
      'author_id' => $admin->id,
    ]);
  }
}
