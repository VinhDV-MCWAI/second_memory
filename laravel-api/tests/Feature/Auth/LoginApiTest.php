<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Enums\AuditEvent;
use App\Models\Audit\AuditLog;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

final class LoginApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const string LOGIN_URL = '/api/admin/credential/login';

    private const string CSRF_COOKIE_URL = '/api/sanctum/csrf-cookie';

    public function test_csrf_cookie_endpoint_sets_the_xsrf_cookie(): void
    {
        $response = $this->get(self::CSRF_COOKIE_URL);

        $response->assertNoContent();
        $this->assertArrayHasKey('XSRF-TOKEN', $this->cookiesOf($response));
    }

    public function test_login_success_starts_a_session_and_returns_the_admin(): void
    {
        $admin = AdminMst::factory()->create();

        $response = $this->postJson(self::LOGIN_URL, ['user_name' => $admin->user_name, 'password' => 'password']);

        $response->assertOk()
            ->assertJsonPath('data.id', $admin->id)
            ->assertJsonPath('data.user_name', $admin->user_name)
            ->assertJsonPath('error.status', false)
            ->assertJsonMissingPath('data.password');

        $cookies = $this->cookiesOf($response);
        $this->assertArrayHasKey(config('session.cookie'), $cookies);
        $this->assertArrayNotHasKey('access_token', $cookies);
        $this->assertArrayNotHasKey('refresh_token', $cookies);
    }

    public function test_login_regenerates_the_session_id(): void
    {
        $admin = AdminMst::factory()->create();
        $before = $this->cookiesOf($this->get(self::CSRF_COOKIE_URL));

        $after = $this->cookiesOf($this->call(
            'POST',
            self::LOGIN_URL,
            ['user_name' => $admin->user_name, 'password' => 'password'],
            $before,
        ));

        $this->assertNotSame(
            $before[config('session.cookie')],
            $after[config('session.cookie')],
            'Session fixation: the session ID must change on login'
        );
    }

    public function test_login_validation_errors(): void
    {
        $this->postJson(self::LOGIN_URL, [])
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['user_name', 'password']]]);
    }

    public function test_every_login_failure_returns_the_same_response(): void
    {
        $admin = AdminMst::factory()->create();
        $deleted = AdminMst::factory()->create(['is_delete' => true]);
        $disabled = AdminMst::factory()->create(['is_active' => false]);

        $attempts = [
            'wrong password' => ['user_name' => $admin->user_name, 'password' => 'wrong-password'],
            'unknown user' => ['user_name' => 'nobody-here', 'password' => 'password'],
            'deleted admin' => ['user_name' => $deleted->user_name, 'password' => 'password'],
            'disabled admin' => ['user_name' => $disabled->user_name, 'password' => 'password'],
        ];

        foreach ($attempts as $case => $payload) {
            $response = $this->postJson(self::LOGIN_URL, $payload);

            $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
            $this->assertSame(Messages::E0401, $response->json('error.messages'), $case);
        }
    }

    public function test_login_is_locked_for_a_while_after_too_many_failures(): void
    {
        $admin = AdminMst::factory()->create();
        $wrong = ['user_name' => $admin->user_name, 'password' => 'wrong-password'];
        $right = ['user_name' => $admin->user_name, 'password' => 'password'];

        for ($i = 0; $i < CommonVal::LOGIN_MAX_ATTEMPTS; $i++) {
            $this->postJson(self::LOGIN_URL, $wrong)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        }

        // Locked: even the right password is refused, and the client is told when to retry
        $this->postJson(self::LOGIN_URL, $right)
            ->assertStatus(CommonVal::HTTP_TOO_MANY_REQUESTS)
            ->assertHeader('Retry-After');

        // The lock expires by itself (the old counter locked the account forever)
        $this->travel(CommonVal::LOGIN_LOCK_SECONDS + 1)->seconds();
        $this->postJson(self::LOGIN_URL, $right)->assertOk();
    }

    public function test_successful_login_resets_the_failure_counter(): void
    {
        $admin = AdminMst::factory()->create();
        $wrong = ['user_name' => $admin->user_name, 'password' => 'wrong-password'];

        for ($i = 0; $i < CommonVal::LOGIN_MAX_ATTEMPTS - 1; $i++) {
            $this->postJson(self::LOGIN_URL, $wrong);
        }
        $this->loginAs($admin);

        $this->postJson(self::LOGIN_URL, $wrong)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_login_from_outside_the_spa_is_refused(): void
    {
        $admin = AdminMst::factory()->create();

        // No Referer / Origin from a stateful domain: Sanctum starts no session
        $this->withServerVariables(['HTTP_REFERER' => 'http://evil.example']);

        $this->postJson(self::LOGIN_URL, ['user_name' => $admin->user_name, 'password' => 'password'])
            ->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_logins_and_logouts_are_audited(): void
    {
        $admin = AdminMst::factory()->create();

        $this->postJson(self::LOGIN_URL, ['user_name' => $admin->user_name, 'password' => 'wrong-password'])->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->postJson(self::LOGIN_URL, ['user_name' => 'nobody-here', 'password' => 'password'])->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $cookies = $this->loginAs($admin);
        $this->call('POST', '/api/admin/credential/logout', [], $cookies)->assertOk();

        $logs = AuditLog::where('auditable_type', 'admin')->orderBy('id')->get();
        $this->assertSame(
            [AuditEvent::LOGIN_FAILED, AuditEvent::LOGIN_FAILED, AuditEvent::LOGGED_IN, AuditEvent::LOGGED_OUT],
            $logs->pluck('event')->all()
        );
        // A failed login never names an admin id (no user enumeration through the log)
        $this->assertNull($logs[0]->auditable_id);
        $this->assertSame(['user_name' => 'nobody-here'], $logs[1]->new_values);
        $this->assertSame($admin->id, $logs[2]->auditable_id);
        $this->assertSame($admin->id, $logs[2]->admin_mst_id);
        $this->assertSame($admin->id, $logs[3]->admin_mst_id);
        $this->assertStringNotContainsString('password', (string) json_encode($logs->pluck('new_values')));
    }
}
