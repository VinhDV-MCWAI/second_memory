<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use App\Models\Master\AdminSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * "Remember me" (AUTH-05): a remembered device signs in again by itself for 30 days after its session
 * expired. It is remembered per device, so signing that device out forgets it and no other device.
 */
final class RememberMeTest extends TestCase
{
    use AuthenticatesAdmins;

    private const string LOGIN_URL = '/api/admin/credential/login';

    private const string ME_URL = '/api/admin/credential/me';

    private const string LIST_URL = '/api/admin/admin-session/list';

    public function test_a_remembered_device_signs_in_again_after_its_session_expired(): void
    {
        $admin = AdminMst::factory()->create();
        $device = $this->remember($admin);
        $row = AdminSession::query()->where('admin_id', $admin->id)->sole();
        $this->assertNotNull($row->remembered_until);

        $this->travel((int) config('session.lifetime') + 60)->minutes();
        $this->call('GET', self::ME_URL, [], $device)->assertOk()->assertJsonPath('data.id', $admin->id);

        // Same row: the screen still shows when the device first signed in
        $this->assertSame([$row->id], AdminSession::query()->where('admin_id', $admin->id)->pluck('id')->all());
        $this->assertNotSame($row->session_id, $row->fresh()?->session_id);
        $this->assertDatabaseHas('audit_log', ['auditable_id' => $admin->id, 'event' => 'logged_in', 'new_values->via' => 'remembered_device']);
    }

    public function test_without_remember_me_the_device_must_sign_in_again(): void
    {
        $admin = AdminMst::factory()->create();
        $response = $this->postJson(self::LOGIN_URL, ['user_name' => $admin->user_name, 'password' => 'password'])->assertOk();

        $this->assertArrayNotHasKey(CommonVal::REMEMBER_COOKIE, $this->cookiesOf($response));
        $this->assertNull(AdminSession::query()->where('admin_id', $admin->id)->value('remembered_until'));
    }

    public function test_remember_me_runs_out_after_thirty_days(): void
    {
        $device = $this->remember(AdminMst::factory()->create());

        $this->travel(CommonVal::REMEMBER_DAYS)->days();
        $this->travel(1)->hours();

        $this->call('GET', self::ME_URL, [], $device)
            ->assertStatus(CommonVal::HTTP_UNAUTHORIZED)
            ->assertCookieExpired(CommonVal::REMEMBER_COOKIE);
    }

    public function test_signing_the_device_out_from_another_device_forgets_it_only(): void
    {
        $admin = AdminMst::factory()->create();
        $phone = $this->remember($admin);
        $laptop = $this->remember($admin);
        $phoneRow = AdminSession::query()->where('admin_id', $admin->id)->orderBy('id')->firstOrFail();

        $this->call('POST', '/api/admin/admin-session/delete', ['ids' => [$phoneRow->id]], $laptop)->assertOk();
        $this->travel((int) config('session.lifetime') + 60)->minutes();

        $this->call('GET', self::ME_URL, [], $phone)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->call('GET', self::ME_URL, [], $laptop)->assertOk();
    }

    public function test_logging_out_forgets_the_device(): void
    {
        $device = $this->remember(AdminMst::factory()->create());

        $this->call('POST', '/api/admin/credential/logout', [], $device)->assertOk()->assertCookieExpired(CommonVal::REMEMBER_COOKIE);

        // Even if the browser kept the cookie, it no longer counts
        $this->call('GET', self::ME_URL, [], Arr::only($device, [CommonVal::REMEMBER_COOKIE]))->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_a_disabled_admin_is_not_signed_in_again(): void
    {
        $admin = AdminMst::factory()->create();
        $device = $this->remember($admin);
        $admin->forceFill(['is_active' => false])->save();

        $this->call('GET', self::ME_URL, [], Arr::only($device, [CommonVal::REMEMBER_COOKIE]))->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_a_forged_token_does_not_count(): void
    {
        $admin = AdminMst::factory()->create();
        $this->remember($admin);
        $rowId = AdminSession::query()->where('admin_id', $admin->id)->value('id');
        $forged = [CommonVal::REMEMBER_COOKIE => encrypt($rowId.'|'.str_repeat('x', 60), false)];

        $this->call('GET', self::ME_URL, [], $forged)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_a_remembered_device_stays_listed_and_is_not_pruned_while_remembered(): void
    {
        $admin = AdminMst::factory()->create();
        $this->remember($admin);
        $device = $this->loginAs($admin);

        $this->travel(2)->days();
        $device = $this->loginAs($admin);
        Artisan::call('auth:prune-sessions');

        $sessions = $this->call('GET', self::LIST_URL, [], $device)->assertOk()->json('data.data');
        $this->assertCount(2, $sessions);
        $this->assertNotNull($sessions[1]['remembered_until']);
    }

    /**
     * Log in with "remember me" and return the cookies.
     *
     * @return array<string, string>
     */
    private function remember(AdminMst $admin): array
    {
        $response = $this->postJson(self::LOGIN_URL, ['user_name' => $admin->user_name, 'password' => 'password', 'remember' => true])
            ->assertOk();

        return $this->cookiesOf($response);
    }
}
