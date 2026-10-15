<?php

declare(strict_types=1);

namespace Tests\Feature\Master\AdminMst;

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Enums\AuditEvent;
use App\Models\Audit\AuditLog;
use App\Models\Master\AdminMst;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Spatie\Permission\Models\Role;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

class UpdateAdminMstTest extends TestCase
{
    use AuthenticatesAdmins;
    use DatabaseTransactions;

    protected string $loginUrl = '/api/admin/credential/login';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }

    protected function getUpdateUrl($id)
    {
        return "/api/admin/admin-mst/update/{$id}";
    }

    protected function getAuthCookies(AdminMst $admin): array
    {
        return $this->loginAsOwner($admin);
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

        $this->assertDatabaseHas('audit_log', [
            'auditable_type' => 'admin',
            'auditable_id' => $admin->id,
            'event' => AuditEvent::UPDATED->value,
            'new_values->first_name' => 'Updated',
        ]);
    }

    public function test_the_last_active_owner_cannot_demote_or_deactivate_themselves()
    {
        $owner = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($owner);

        $demote = [...$this->getValidPayload($owner), 'role_ids' => [$this->roleId(AdminRole::VIEWER)]];
        $this->assertCustomValidationErrors($this->call('PUT', $this->getUpdateUrl($owner->id), $demote, $cookies), 'role_ids');

        $deactivate = [...$this->getValidPayload($owner), 'is_active' => 0];
        $this->assertCustomValidationErrors($this->call('PUT', $this->getUpdateUrl($owner->id), $deactivate, $cookies), 'role_ids');

        $this->assertTrue($owner->fresh()->hasRole(AdminRole::OWNER->value));
    }

    public function test_an_owner_can_change_another_admins_role()
    {
        $owner = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($owner);
        $viewer = AdminMst::factory()->create();

        $promote = [...$this->getValidPayload($viewer), 'role_ids' => [$this->roleId(AdminRole::OWNER), $this->roleId(AdminRole::VIEWER)]];
        $this->call('PUT', $this->getUpdateUrl($viewer->id), $promote, $cookies)->assertStatus(CommonVal::HTTP_OK);

        $this->assertEqualsCanonicalizing([AdminRole::OWNER->value, AdminRole::VIEWER->value], $viewer->fresh()->getRoleNames()->all());
        $audit = AuditLog::query()->where(['auditable_type' => 'admin', 'auditable_id' => $viewer->id])->latest('id')->firstOrFail();
        $this->assertSame([AdminRole::VIEWER->value], array_column($audit->old_values['roles'], 'name'));
        $this->assertEqualsCanonicalizing([AdminRole::OWNER->value, AdminRole::VIEWER->value], array_column($audit->new_values['roles'], 'name'));
    }

    public function test_an_admin_can_be_left_without_roles_but_unknown_roles_are_rejected()
    {
        $cookies = $this->getAuthCookies(AdminMst::factory()->create());
        $viewer = AdminMst::factory()->create();

        $this->assertCustomValidationErrors($this->call('PUT', $this->getUpdateUrl($viewer->id), [...$this->getValidPayload($viewer), 'role_ids' => [999999]], $cookies), 'role_ids.0');

        $this->call('PUT', $this->getUpdateUrl($viewer->id), [...$this->getValidPayload($viewer), 'role_ids' => []], $cookies)->assertStatus(CommonVal::HTTP_OK);
        $this->assertSame([], $viewer->fresh()->getRoleNames()->all());
    }

    private function roleId(AdminRole $role): int
    {
        return (int) Role::findByName($role->value, CommonVal::ADMIN_GUARD)->id;
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
