<?php

declare(strict_types=1);

namespace Tests\Feature\History\Master\ApiMstHist;

use App\Constants\CommonVal;
use App\Models\History\Master\ApiMstHist;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListApiMstHistTest extends TestCase
{
    use RefreshDatabase;

    private string $listUrl = 'api/admin/api-mst-hist/list';

    /**
     * Helper to get authenticated cookies with 'root' role
     */
    private function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }

        $this->grantAccessTo($rootRole, 'GET', 'api/admin/api-mst-hist/list');

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

        $api = ApiMst::create([
            'type' => $type,
            'name' => "Endpoint $method $path",
            'path' => $path,
            'is_active' => 1,
            'feature_mst_id' => $feature->id,
            'is_delete' => 0,
        ]);

        DB::table('api_role_mst')->insertOrIgnore([
            'api_mst_id' => $api->id,
            'role_mst_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test [MST_HIST_LST_001] Unauthenticated
     */
    public function test_ms_t_his_t_ls_t_001_unauthenticated()
    {
        $response = $this->json('GET', $this->listUrl);
        $response->assertStatus(401);
    }

    /**
     * Test [MST_HIST_LST_002] Date Format Invalid
     */
    public function test_ms_t_his_t_ls_t_002_invalid_date_format()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'from_date' => 'invalid-date',
        ];

        $response = $this->call('GET', $this->listUrl, $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        // Custom error structure check
        $errors = $response->json('error.messages');
        $this->assertArrayHasKey('from_date', $errors);
    }

    /**
     * Test [MST_HIST_LST_003] To Date before From Date
     */
    public function test_ms_t_his_t_ls_t_003_to_date_before_from_date()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = [
            'from_date' => now()->format('d/m/Y'),
            'to_date' => now()->subDay()->format('d/m/Y'),
        ];

        $response = $this->call('GET', $this->listUrl, $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $errors = $response->json('error.messages');
        $this->assertArrayHasKey('to_date', $errors);
    }

    /**
     * Test [MST_HIST_LST_004] Filtering
     */
    public function test_ms_t_his_t_ls_t_004_filtering()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        // Create history records (Using Model direct create as Factory might not exist or be needed)
        // Assuming ApiMstHist can be created.
        $hist1 = new ApiMstHist;
        $hist1->api_mst_id = 1;
        $hist1->type = 1;
        $hist1->name = 'Hist1';
        $hist1->path = 'path/1';
        $hist1->is_active = 1;
        $hist1->feature_mst_id = 1;
        $hist1->action = 1; // Create
        $hist1->author_id = $admin->id;
        $hist1->created_at = now();
        $hist1->save();

        $hist2 = new ApiMstHist;
        $hist2->api_mst_id = 2;
        $hist2->type = 1;
        $hist2->name = 'Hist2';
        $hist2->path = 'path/2';
        $hist2->is_active = 1;
        $hist2->feature_mst_id = 1;
        $hist2->action = 2; // Update
        $hist2->author_id = $admin->id;
        $hist2->created_at = now();
        $hist2->save();

        // Filter by action
        $payload = [
            'action' => 1,
        ];

        $response = $this->call('GET', $this->listUrl, $payload, $cookies);
        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $hist1->id]);
        $response->assertJsonMissing(['id' => $hist2->id]);
    }

    /**
     * Test [MST_HIST_GEN_001] Response Structure
     */
    public function test_ms_t_his_t_ge_n_001_response_structure()
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $hist1 = new ApiMstHist;
        $hist1->api_mst_id = 1;
        $hist1->type = 1;
        $hist1->name = 'Hist1';
        $hist1->path = 'path/1';
        $hist1->is_active = 1;
        $hist1->feature_mst_id = 1;
        $hist1->action = 1;
        $hist1->author_id = $admin->id;
        $hist1->created_at = now();
        $hist1->save();

        $response = $this->call('GET', $this->listUrl, [], $cookies);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'api_mst_id',
                        'type',
                        'name',
                        'path',
                        'is_active',
                        'feature_mst_id',
                        'action',
                        'author_id',
                        'created_at', // History has created_at
                        // Relations might be loaded: apiMst, author
                    ],
                ],
            ],
        ]);
    }
}
