<?php

declare(strict_types=1);

namespace Tests\Feature\Master\AdminMst;

use App\Constants\CommonVal;
use App\Enums\ActionType;
use App\Models\Master\AdminMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

class UpdateAdminMstTest extends TestCase
{
    use DatabaseTransactions;
    use GrantsApiAccess;

    protected string $loginUrl = '/api/admin/credential/login';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    protected function getUpdateUrl($id)
    {
        return "/api/admin/admin-mst/update/{$id}";
    }

    protected function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        }
        $this->grantAccessTo($rootRole, 'PUT', 'api/admin/admin-mst/update/{id}');

        if (! DB::table('admin_role_mst')->where('admin_mst_id', $admin->id)->where('role_mst_id', $rootRole->id)->exists()) {
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

        foreach ((array) $keys as $key) {
            $this->assertArrayHasKey($key, $json['error']['messages']);
        }
    }

    /**
     * MST_UPD_001: Method Not Allowed
     */
    public function test_ms_t_up_d_001_method_not_allowed()
    {
        $admin = AdminMst::factory()->create();
        $response = $this->postJson($this->getUpdateUrl($admin->id), []);
        $response->assertStatus(CommonVal::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * MST_UPD_002: Ignores Body ID Mismatch
     * System trusts Route ID over Body ID
     */
    public function test_ms_t_up_d_002_ignores_body_id_mismatch()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload($admin);

        // Passing ID that does not exist in Body
        $payload['id'] = 999999;

        $response = $this->call('PUT', $this->getUpdateUrl($admin->id), $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);
    }

    /**
     * MST_UPD_004: Update Self Same Email
     * Test for potential issue where unique rule doesn't ignore current ID
     */
    public function test_ms_t_up_d_004_update_self_same_email()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload($admin);
        // keep same email
        $payload['email'] = $admin->email;

        $response = $this->call('PUT', $this->getUpdateUrl($admin->id), $payload, $cookies);

        // Validation rule fixed to ignore ID, so this should pass now.
        $response->assertStatus(CommonVal::HTTP_OK);
    }

    /**
     * MST_UPD_005: Update Duplicate Email
     */
    public function test_ms_t_up_d_005_update_duplicate_email()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $other = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload($admin);
        $payload['email'] = $other->email;

        $response = $this->call('PUT', $this->getUpdateUrl($admin->id), $payload, $cookies);

        $this->assertCustomValidationErrors($response, ['email']);
    }

    /**
     * MST_UPD_006: Validation Max Lengths
     */
    public function test_ms_t_up_d_006_validation_max_lengths()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload($admin);
        $payload['user_name'] = str_repeat('a', 51);

        $response = $this->call('PUT', $this->getUpdateUrl($admin->id), $payload, $cookies);

        $this->assertCustomValidationErrors($response, ['user_name']);
    }

    /**
     * MST_UPD_007: Success Update & History
     */
    public function test_ms_t_up_d_007_success_update_and_history()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload($admin);
        $payload['first_name'] = 'Updated';

        $response = $this->call('PUT', $this->getUpdateUrl($admin->id), $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);

        $this->assertDatabaseHas('admin_mst', [
            'id' => $admin->id,
            'first_name' => 'Updated',
        ]);

        $this->assertDatabaseHas('admin_mst_hist', [
            'admin_mst_id' => $admin->id,
            'action' => ActionType::UPDATE->value,
            'first_name' => 'Updated',
        ]);
    }

    private function getValidPayload($admin): array
    {
        return [
            'id' => $admin->id,
            'email' => $admin->email,
            'user_name' => $admin->user_name,
            'password' => 'Password123',
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'gender' => $admin->gender,
            'status' => $admin->status,
            'is_active' => $admin->is_active,
            'is_delete' => $admin->is_delete,
            // 'birth' => '01/01/2000' // Optional defaults
        ];
    }
}
