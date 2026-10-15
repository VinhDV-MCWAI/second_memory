<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Models\Master\AdminMst;
use App\Models\Master\AdminSession;
use Illuminate\Support\Facades\Artisan;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Sessions screen (AUTH-04): an admin sees the devices they are signed in on and signs out the others.
 */
final class AdminSessionTest extends TestCase
{
    use AuthenticatesAdmins;

    private const string LIST_URL = '/api/admin/admin-session/list';

    private const string DELETE_URL = '/api/admin/admin-session/delete';

    private const string DELETE_OTHERS_URL = '/api/admin/admin-session/delete-others';

    private const string ME_URL = '/api/admin/credential/me';

    public function test_login_lists_the_device_and_marks_the_current_one(): void
    {
        $admin = AdminMst::factory()->create();
        $device = $this->loginAs($admin);

        $sessions = $this->call('GET', self::LIST_URL, [], $device)->assertOk()->json('data.data');

        $this->assertCount(1, $sessions);
        $this->assertTrue($sessions[0]['is_current']);
        $this->assertSame('127.0.0.1', $sessions[0]['ip_address']);
        $this->assertArrayNotHasKey('session_id', $sessions[0]);
    }

    public function test_a_viewer_without_permissions_can_manage_own_sessions(): void
    {
        $viewer = AdminMst::factory()->create();
        $viewer->syncRoles([]);
        $device = $this->loginAs($viewer);

        $this->call('GET', self::LIST_URL, [], $device)->assertOk();
        $this->call('POST', self::DELETE_OTHERS_URL, [], $device)->assertOk();
    }

    public function test_ending_another_device_logs_it_out(): void
    {
        $admin = AdminMst::factory()->create();
        $thisDevice = $this->loginAs($admin);
        $otherDevice = $this->loginAs($admin);
        $otherId = $this->sessionIdsOf($admin)[1];

        $this->call('POST', self::DELETE_URL, ['ids' => [$otherId]], $thisDevice)->assertOk()->assertJsonPath('data', 1);

        $this->call('GET', self::ME_URL, [], $otherDevice)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->call('GET', self::ME_URL, [], $thisDevice)->assertOk();
        $this->assertCount(1, $this->call('GET', self::LIST_URL, [], $thisDevice)->json('data.data'));
        $this->assertDatabaseHas('audit_log', ['auditable_id' => $admin->id, 'event' => 'logged_out', 'admin_mst_id' => $admin->id]);
    }

    public function test_the_session_in_use_cannot_be_ended_here(): void
    {
        $admin = AdminMst::factory()->create();
        $device = $this->loginAs($admin);

        $this->call('POST', self::DELETE_URL, ['ids' => $this->sessionIdsOf($admin)], $device)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonPath('error.messages.ids.0', Messages::E0043);
        $this->call('GET', self::ME_URL, [], $device)->assertOk();
    }

    public function test_sessions_of_another_admin_are_left_alone(): void
    {
        $other = AdminMst::factory()->create();
        $otherDevice = $this->loginAs($other);
        $device = $this->loginAs(AdminMst::factory()->create());

        $this->call('POST', self::DELETE_URL, ['ids' => $this->sessionIdsOf($other)], $device)->assertOk()->assertJsonPath('data', 0);

        $this->call('GET', self::ME_URL, [], $otherDevice)->assertOk();
    }

    public function test_sign_out_everywhere_else_keeps_this_device(): void
    {
        $admin = AdminMst::factory()->create();
        $firstDevice = $this->loginAs($admin);
        $secondDevice = $this->loginAs($admin);
        $thisDevice = $this->loginAs($admin);

        $this->call('POST', self::DELETE_OTHERS_URL, [], $thisDevice)->assertOk()->assertJsonPath('data', 2);

        $this->call('GET', self::ME_URL, [], $firstDevice)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->call('GET', self::ME_URL, [], $secondDevice)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->call('GET', self::ME_URL, [], $thisDevice)->assertOk();
    }

    public function test_logout_removes_the_device(): void
    {
        $admin = AdminMst::factory()->create();
        $device = $this->loginAs($admin);

        $this->call('POST', '/api/admin/credential/logout', [], $device)->assertOk();

        $this->assertSame([], $this->sessionIdsOf($admin));
    }

    public function test_a_session_written_back_after_it_was_ended_is_logged_out_at_its_next_refresh(): void
    {
        $admin = AdminMst::factory()->create();
        $device = $this->loginAs($admin);
        // As if a request still running on that device wrote the session back after the row was deleted
        AdminSession::query()->where('admin_id', $admin->id)->delete();

        $this->call('GET', self::ME_URL, [], $device)->assertOk();
        $this->travel(CommonVal::SESSION_TOUCH_SECONDS + 1)->seconds();
        $this->call('GET', self::ME_URL, [], $device)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_using_a_session_refreshes_its_last_use_at_most_every_few_minutes(): void
    {
        $admin = AdminMst::factory()->create();
        $device = $this->loginAs($admin);
        $signedInAt = AdminSession::query()->where('admin_id', $admin->id)->value('last_used_at');

        $this->travel(CommonVal::SESSION_TOUCH_SECONDS - 10)->seconds();
        $this->call('GET', self::ME_URL, [], $device)->assertOk();
        $this->assertEquals($signedInAt, AdminSession::query()->where('admin_id', $admin->id)->value('last_used_at'));

        $this->travel(20)->seconds();
        $this->call('GET', self::ME_URL, [], $device)->assertOk();
        $this->assertNotEquals($signedInAt, AdminSession::query()->where('admin_id', $admin->id)->value('last_used_at'));
    }

    public function test_a_new_password_or_deactivation_removes_the_devices(): void
    {
        $owner = AdminMst::factory()->create();
        $ownerDevice = $this->loginAsOwner($owner);
        $this->loginAs($owner);
        $viewer = AdminMst::factory()->create();
        $this->loginAs($viewer);

        $this->call('PUT', "/api/admin/admin-mst/update/{$owner->id}", $this->payload($owner, ['password' => 'NewPassword123']), $ownerDevice)->assertOk();
        $this->assertCount(1, $this->call('GET', self::LIST_URL, [], $ownerDevice)->json('data.data'));

        $this->call('PUT', "/api/admin/admin-mst/update/{$viewer->id}", $this->payload($viewer, ['is_active' => false]), $ownerDevice)->assertOk();
        $this->assertSame([], $this->sessionIdsOf($viewer));
    }

    public function test_deleting_an_admin_removes_the_devices(): void
    {
        $ownerDevice = $this->loginAsOwner(AdminMst::factory()->create());
        $viewer = AdminMst::factory()->create();
        $this->loginAs($viewer);

        $this->call('POST', '/api/admin/admin-mst/delete', ['ids' => [$viewer->id]], $ownerDevice)->assertOk();

        $this->assertSame([], $this->sessionIdsOf($viewer));
    }

    public function test_ids_are_required(): void
    {
        $device = $this->loginAs(AdminMst::factory()->create());

        $this->call('POST', self::DELETE_URL, ['ids' => []], $device)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    }

    public function test_guests_are_refused(): void
    {
        $this->getJson(self::LIST_URL)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->postJson(self::DELETE_OTHERS_URL)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_prune_removes_rows_unused_for_longer_than_a_session_lives(): void
    {
        $admin = AdminMst::factory()->create();
        $this->loginAs($admin);
        $this->travel((int) config('session.lifetime') + 10)->minutes();
        $this->loginAs($admin);

        Artisan::call('auth:prune-sessions');

        $this->assertCount(1, $this->sessionIdsOf($admin));
    }

    /**
     * @return list<int> row ids, in login order
     */
    private function sessionIdsOf(AdminMst $admin): array
    {
        return AdminSession::query()->where('admin_id', $admin->id)->orderBy('id')->pluck('id')->all();
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function payload(AdminMst $admin, array $changes): array
    {
        return [
            'email' => $admin->email,
            'user_name' => $admin->user_name,
            'password' => null,
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'gender' => $admin->gender,
            'status' => $admin->status,
            'is_active' => $admin->is_active,
            ...$changes,
        ];
    }
}
