<?php

namespace Tests\Feature\History\Master\ApiMstHist;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\History\Master\ApiMstHist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteApiMstHistTest extends TestCase
{
  use RefreshDatabase;

  private string $baseUrl = 'api/admin/api-mst-hist/delete';

  /**
   * Helper to get authenticated cookies with 'root' role
   */
  private function getAuthCookies(AdminMst $admin): array
  {
    $rootRole = \App\Models\Master\RoleMst::where('name', 'root')->first();
    if (!$rootRole) {
      $rootRole = \App\Models\Master\RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
    }

    $this->grantAccessTo($rootRole, 'POST', 'api/admin/api-mst-hist/delete');

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

  private function grantAccessTo(\App\Models\Master\RoleMst $role, string $method, string $path)
  {
    $typeMap = ['GET' => 0, 'POST' => 1, 'PUT' => 2, 'PATCH' => 3, 'DELETE' => 4];
    $type = $typeMap[strtoupper($method)] ?? 0;

    $feature = \App\Models\Master\FeatureMst::firstOrCreate([
      'name' => 'System Features',
      'group_name' => 'System',
      'status' => 1,
      'is_delete' => 0
    ]);

    $api = \App\Models\Master\ApiMst::firstOrCreate(
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
   * Test [MST_HIST_DEL_001] Wrong HTTP Method
   */
  public function test_MST_HIST_DEL_001_wrong_http_method()
  {
    $hist = ApiMstHist::create([
      'api_mst_id' => 1,
      'name' => 'test',
      'feature_mst_id' => 1,
      'action' => 1,
      'author_id' => 1
    ]);
    $url = $this->baseUrl;

    $response = $this->getJson($url);
    $response->assertStatus(405);
  }

  /**
   * Test [MST_HIST_DEL_002] Unauthenticated
   */
  public function test_MST_HIST_DEL_002_unauthenticated()
  {
    $hist = ApiMstHist::create([
      'api_mst_id' => 1,
      'name' => 'test',
      'feature_mst_id' => 1,
      'action' => 1,
      'author_id' => 1
    ]);
    $url = $this->baseUrl;

    $response = $this->postJson($url, []);
    $response->assertStatus(401);
  }

  /**
   * Test [MST_HIST_DEL_004] Missing ids
   */
  public function test_MST_HIST_DEL_004_missing_ids()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    // URL ID is ignored
    $url = $this->baseUrl;

    $response = $this->call('POST', $url, [], $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('ids', $response->json('error.messages'));
  }

  /**
   * Test [MST_HIST_DEL_005] Ids not array
   */
  public function test_MST_HIST_DEL_005_ids_not_array()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);
    $url = $this->baseUrl;

    $payload = ['ids' => 'abc'];

    $response = $this->call('POST', $url, $payload, $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('ids', $response->json('error.messages'));
  }

  /**
   * Test [MST_HIST_DEL_006] Ids non-existent
   */
  public function test_MST_HIST_DEL_006_ids_non_existent()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);
    $url = $this->baseUrl;

    $payload = ['ids' => [999999]];

    $response = $this->call('POST', $url, $payload, $cookies);
    $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    $this->assertArrayHasKey('ids.0', $response->json('error.messages'));
  }

  /**
   * Test [MST_HIST_DEL_007] Success
   */
  public function test_MST_HIST_DEL_007_success()
  {
    $admin = AdminMst::factory()->create();
    $cookies = $this->getAuthCookies($admin);

    $h1 = ApiMstHist::create(['api_mst_id' => 1, 'feature_mst_id' => 1, 'action' => 1, 'author_id' => 1, 'name' => 'H1']);
    $h2 = ApiMstHist::create(['api_mst_id' => 1, 'feature_mst_id' => 1, 'action' => 1, 'author_id' => 1, 'name' => 'H2']);

    $url = $this->baseUrl; // ID ignored

    $payload = ['ids' => [$h1->id, $h2->id]];

    $response = $this->call('POST', $url, $payload, $cookies);

    if ($response->status() !== 200) {
      $response->dump();
    }
    $response->assertStatus(200);

    // Verify Hard Delete
    $this->assertDatabaseMissing('api_mst_hist', ['id' => $h1->id]);
    $this->assertDatabaseMissing('api_mst_hist', ['id' => $h2->id]);
  }
}
