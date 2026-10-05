<?php

namespace Tests\Feature\Integration;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use App\Models\Master\TokenMst;
use App\Models\Management\UserMgmt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class FullSystemFlowTest extends TestCase
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

  private function getAuthCookies(AdminMst $admin): array
  {
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

  private function grantAccessToAdmin(AdminMst $admin, string $method, string $path)
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

    $feature = FeatureMst::firstOrCreate([
      'name' => 'Integration Feature',
      'group_name' => 'System',
      'status' => 1,
      'is_delete' => 0
    ]);

    $typeMap = ['GET' => 0, 'POST' => 1, 'PUT' => 2, 'DELETE' => 4];
    $type = $typeMap[strtoupper($method)] ?? 0;

    // Use a unique name for each path to avoid unique constraint if re-using 'Int Test POST'
    $pathHash = mb_substr(md5($path), 0, 6); // Short hash
    $api = ApiMst::firstOrCreate(
      ['path' => $path, 'type' => $type],
      [
        'name' => "IT $method $pathHash",
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

  // Debug Helper
  private function checkError($response)
  {
    if ($response->status() === 422) {
      dump('422 Error:', $response->json());
    }
  }

  public function test_scenario_1_admin_provisioning_flow()
  {
    // 1. Root Login setup
    $rootAdmin = AdminMst::factory()->create(['user_name' => 'root_user']);

    // Grant permissions needed
    $storeUserUrl = 'api/admin/user-mgmt/store';
    $this->grantAccessToAdmin($rootAdmin, 'POST', $this->loginUrl);
    $this->grantAccessToAdmin($rootAdmin, 'POST', $storeUserUrl);
    $this->grantAccessToAdmin($rootAdmin, 'PUT', 'api/admin/user-mgmt/update/{id}');

    // Login (After granting perms)
    $rootCookies = $this->getAuthCookies($rootAdmin);

    $newUserPayload = [
      'email' => 'integration_new@gmail.com', // Use real domain for DNS validation
      'user_name' => 'new_admin',
      'password' => 'Password123!',
      'first_name' => 'New',
      'last_name' => 'Admin',
      'is_active' => 1,
      'is_delete' => 0,
      'gender' => 1,
      'birth' => '01/01/2000',
      'phone_number' => '0901234567'
    ];

    $response = $this->call('POST', $storeUserUrl, $newUserPayload, $rootCookies);
    $response->assertStatus(200);
    $userId = $response->json('data');

    // Verify User Created
    $this->assertDatabaseHas('user_mgmt', ['email' => 'integration_new@gmail.com']);

    // Update User
    $updatePayload = array_merge($newUserPayload, ['id' => $userId, 'first_name' => 'Updated']);
    $response = $this->call('PUT', 'api/admin/user-mgmt/update/' . $userId, $updatePayload, $rootCookies);
    $response->assertStatus(200);

    $this->assertDatabaseHas('user_mgmt', ['id' => $userId, 'first_name' => 'Updated']);
  }

  public function test_scenario_2_content_management_lifecycle()
  {
    $admin = AdminMst::factory()->create();

    $sliderUrl = 'api/admin/slider-mgmt/store';
    $socialUrl = 'api/admin/social-mgmt/store';
    $listSocialUrl = 'api/admin/social-mgmt/list';
    $updateSliderUrl = 'api/admin/slider-mgmt/update/{id}';
    $deleteSocialUrl = 'api/admin/social-mgmt/delete/{id}';

    $this->grantAccessToAdmin($admin, 'POST', $this->loginUrl);
    $this->grantAccessToAdmin($admin, 'POST', $sliderUrl);
    $this->grantAccessToAdmin($admin, 'POST', $socialUrl);
    $this->grantAccessToAdmin($admin, 'GET', $listSocialUrl);
    $this->grantAccessToAdmin($admin, 'PUT', $updateSliderUrl);
    $this->grantAccessToAdmin($admin, 'DELETE', $deleteSocialUrl);

    $cookies = $this->getAuthCookies($admin);

    // 1. Create Slider
    $sliderPayload = [
      'title' => 'Integration Slider',
      'slug' => 'int-slider',
      'link' => 'http://test.com',
      'image' => 'img.jpg',
      'status' => 1,
      'is_delete' => 0
    ];

    $response = $this->call('POST', $sliderUrl, $sliderPayload, $cookies);
    $this->checkError($response);
    $response->assertStatus(200);
    $sliderId = $response->json('data');

    // 2. Create Social
    $socialPayload = [
      'name' => 'Integration Social',
      'slug' => 'integration-social',
      'link' => 'http://social.com',
      'status' => 1,
      'is_display' => 1,
      'image' => 'social.jpg',
      'rank_order' => 1,
      'is_delete' => 0
    ];

    $response = $this->call('POST', $socialUrl, $socialPayload, $cookies);
    $response->assertStatus(200);
    $socialId = $response->json('data');

    // 3. List Content to Verify
    $response = $this->call('GET', $listSocialUrl, [], $cookies);
    $response->assertStatus(200);
    $data = $response->json('data.data');
    $this->assertTrue(collect($data)->contains('id', $socialId));

    // 4. Update Slider
    $realUpdateUrl = 'api/admin/slider-mgmt/update/' . $sliderId;
    $sliderPayload['status'] = 0; // Draft
    $sliderPayload['id'] = $sliderId;

    $response = $this->call('PUT', $realUpdateUrl, $sliderPayload, $cookies);
    $response->assertStatus(200);
    $this->assertDatabaseHas('slider_mgmt', ['id' => $sliderId, 'status' => 0]);

    // 5. Delete Social (Soft Delete)
    $realDeleteUrl = 'api/admin/social-mgmt/delete/' . $socialId;
    $deletePayload = ['ids' => [$socialId]];

    $response = $this->call('DELETE', $realDeleteUrl, $deletePayload, $cookies);
    $response->assertStatus(200);

    $this->assertDatabaseHas('social_mgmt', ['id' => $socialId, 'is_delete' => 1]);
    $this->assertDatabaseHas('social_mgmt_hist', ['social_mgmt_id' => $socialId, 'action' => 3]);
  }

  public function test_scenario_3_token_security()
  {
    $admin = AdminMst::factory()->create();

    $checkUrl = 'api/admin/token-mst/list';
    $this->grantAccessToAdmin($admin, 'POST', $this->loginUrl);
    $this->grantAccessToAdmin($admin, 'GET', $checkUrl);

    // Login
    $cookies = $this->getAuthCookies($admin);
    $accessToken = $cookies['access_token'] ?? null;
    $this->assertNotNull($accessToken);

    // 2. Verify Access
    $response = $this->call('GET', $checkUrl, [], $cookies);
    $response->assertStatus(200);

    // 3. Manually Expire Token (Revoke from Redis)
    $tokenKey = \App\Constants\CommonVal::ADMIN_TYPE . ":{$admin->id}:{$accessToken}";
    $deleted = Redis::del($tokenKey);
    // dump("Removing Token: $tokenKey, Result: $deleted");

    // 4. Access Retry (Should Fail)
    $response = $this->call('GET', $checkUrl, [], $cookies);

    // Assert failure (401 or 403)
    $this->assertNotEquals(200, $response->status(), 'Token should be expired');
  }
}
