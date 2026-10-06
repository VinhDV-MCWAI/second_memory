<?php

declare(strict_types=1);

namespace Tests\Feature\History\Master\ApiMstHist;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Models\History\Master\ApiMstHist;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpdateApiMstHistTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'api/admin/api-mst-hist/update';

    /**
     * Helper to get authenticated cookies with 'root' role
     */
    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        $this->grantAccessTo($rootRole, 'PUT', 'api/admin/api-mst-hist/update/{id}');

        if (! DB::table('admin_role_mst')
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
            'is_delete' => 0,
        ]);

        $api = ApiMst::firstOrCreate(
            ['path' => $path, 'type' => $type],
            [
                'name' => "Endpoint $method $path",
                'is_active' => 1,
                'feature_mst_id' => $feature->id,
                'is_delete' => 0,
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
     * Test [MST_HIST_UPD_001] Wrong HTTP Method
     */
    public function test_ms_t_his_t_up_d_001_wrong_http_method()
    {
        $hist = ApiMstHist::create([
            'api_mst_id' => 1,
            'name' => 'test',
            'feature_mst_id' => 1,
            'action' => 1,
            'author_id' => 1,
        ]);
        $url = $this->baseUrl.'/'.$hist->id;

        $response = $this->getJson($url);
        $response->assertStatus(405);
    }

    /**
     * Test [MST_HIST_UPD_002] Unauthenticated
     */
    public function test_ms_t_his_t_up_d_002_unauthenticated()
    {
        $hist = ApiMstHist::create([
            'api_mst_id' => 1,
            'name' => 'test',
            'feature_mst_id' => 1,
            'action' => 1,
            'author_id' => 1,
        ]);
        $url = $this->baseUrl.'/'.$hist->id;

        $response = $this->putJson($url, []);
        $response->assertStatus(401);
    }

    /**
     * Test [MST_HIST_UPD_003] Invalid Route Parameter
     */
    public function test_ms_t_his_t_up_d_003_invalid_route_parameter()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $url = $this->baseUrl.'/abc';

        $response = $this->call('PUT', $url, [], $cookies);
        // Usually 404 or 422 depending on router constraints. Laravel often returns 404 for type mismatch if constrained.
        // If not constrained, it hits controller validation.
        // Assuming pattern matching or validation triggers 404/422.
        // Checking code: Request validation uses 'id' -> integer RULE, but route param binding might differ.
        // Let's assert 404 as 'abc' is likely not found or route mismatch.
        $response->assertStatus(422);
    }

    /**
     * Test [MST_HIST_UPD_004] ID mismatch / Missing Body ID
     */
    public function test_ms_t_his_t_up_d_004_id_mismatch()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $hist = new ApiMstHist;
        $hist->api_mst_id = 1;
        $hist->feature_mst_id = 1;
        $hist->action = 1;
        $hist->author_id = 1;
        $hist->save();

        $url = $this->baseUrl.'/'.$hist->id;

        // Missing ID in body
        $response = $this->call('PUT', $url, [], $cookies);
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertArrayHasKey('id', $response->json('error.messages'));
    }

    /**
     * Test [MST_HIST_UPD_005] Non-existent ID
     */
    public function test_ms_t_his_t_up_d_005_non_existent_id()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $url = $this->baseUrl.'/999999';

        // We must provide valid body for validation to reach ID exist check?
        // Request rules: 'id' => integer, exists.
        $payload = ['id' => 999999, 'api_mst_id' => 1, 'feature_mst_id' => 1, 'action' => 1, 'author_id' => 1];

        $response = $this->call('PUT', $url, $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertArrayHasKey('id', $response->json('error.messages'));
    }

    /**
     * Test [MST_HIST_UPD_006] Success
     */
    public function test_ms_t_his_t_up_d_006_success()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $feature = FeatureMst::factory()->create();
        $api = ApiMst::factory()->create(['feature_mst_id' => $feature->id]);

        $hist = new ApiMstHist;
        $hist->api_mst_id = $api->id;
        $hist->feature_mst_id = $feature->id;
        $hist->action = 1;
        $hist->author_id = $admin->id;
        $hist->name = 'Old Name';
        $hist->save();

        $url = $this->baseUrl.'/'.$hist->id;

        $payload = [
            'id' => $hist->id,
            'api_mst_id' => $api->id,
            'type' => 2,
            'name' => 'Using New Name',
            'path' => '/hist/new-path',
            'is_active' => IsActive::TRUE->value,
            'feature_mst_id' => $feature->id,
            'action' => 2,
            'author_id' => $admin->id,
        ];

        $response = $this->call('PUT', $url, $payload, $cookies);

        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);

        $this->assertDatabaseHas('api_mst_hist', [
            'id' => $hist->id,
            'name' => 'Using New Name',
            'action' => 2,
        ]);
    }
}
