<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use App\Models\User\EndUser;
use App\Services\System\RegistrationSetting;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\AuthenticatesEndUsers;
use Tests\TestCase;

/**
 * End-user accounts of the public site (ADR-0020, REQ-003).
 */
final class EndUserAccountApiTest extends TestCase
{
    use AuthenticatesAdmins, AuthenticatesEndUsers;

    private const BASE = '/api/user';

    public function test_register_signs_in_with_a_lower_case_unique_username(): void
    {
        $this->call('GET', self::BASE.'/registration')->assertOk()->assertJsonPath('data.open', true);

        $payload = ['username' => ' Ann.Nguyen ', 'display_name' => 'Ann', 'password' => 'secret123', 'password_confirmation' => 'secret123'];
        $response = $this->postJson(self::BASE.'/register', $payload)->assertOk()
            ->assertJsonPath('data.username', 'ann.nguyen')
            ->assertJsonPath('data.display_name', 'Ann');
        $this->assertArrayNotHasKey('id', $response->json('data'));
        $this->call('GET', self::BASE.'/me', [], $this->cookiesOf($response))->assertOk()->assertJsonPath('data.display_name', 'Ann');

        $this->postJson(self::BASE.'/register', [...$payload, 'username' => 'ANN.NGUYEN'])
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['username']]]);
    }

    public function test_register_checks_the_fields(): void
    {
        $this->postJson(self::BASE.'/register', ['username' => 'a b@c', 'password' => 'short', 'password_confirmation' => 'other'])
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['username', 'display_name', 'password']]]);
    }

    public function test_closed_registration_answers_403(): void
    {
        app(RegistrationSetting::class)->setOpen(false);

        $this->call('GET', self::BASE.'/registration')->assertJsonPath('data.open', false);
        $this->postJson(self::BASE.'/register', ['username' => 'bob', 'display_name' => 'Bob', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertForbidden();
        $this->assertDatabaseMissing('end_user', ['username' => 'bob']);
    }

    public function test_login_logout_and_the_same_answer_for_every_failure(): void
    {
        $endUser = EndUser::factory()->create(['username' => 'cat']);
        $locked = EndUser::factory()->locked()->create();

        $cookies = $this->cookiesOf($this->postJson(self::BASE.'/login', ['username' => 'CAT', 'password' => 'password'])->assertOk());
        $this->assertNotNull($endUser->refresh()->last_login_at);

        foreach ([['cat', 'wrong'], ['nobody', 'password'], [$locked->username, 'password']] as [$username, $password]) {
            $this->postJson(self::BASE.'/login', ['username' => $username, 'password' => $password])->assertUnauthorized();
        }

        $this->call('POST', self::BASE.'/logout', [], $cookies)->assertOk();
        $this->call('GET', self::BASE.'/me', [], $cookies)->assertUnauthorized();
    }

    public function test_login_is_throttled_per_username(): void
    {
        $endUser = EndUser::factory()->create();
        for ($i = 0; $i < CommonVal::LOGIN_MAX_ATTEMPTS; $i++) {
            $this->postJson(self::BASE.'/login', ['username' => $endUser->username, 'password' => 'wrong'])->assertUnauthorized();
        }

        $this->postJson(self::BASE.'/login', ['username' => $endUser->username, 'password' => 'password'])->assertTooManyRequests();
    }

    public function test_a_locked_end_user_is_signed_out_on_the_next_request(): void
    {
        $endUser = EndUser::factory()->create();
        $cookies = $this->loginAsEndUser($endUser);
        $this->call('GET', self::BASE.'/me', [], $cookies)->assertOk();

        $endUser->forceFill(['is_active' => false])->save();

        $this->call('GET', self::BASE.'/me', [], $cookies)->assertUnauthorized();
    }

    public function test_change_password_needs_the_current_one(): void
    {
        $endUser = EndUser::factory()->create();
        $cookies = $this->loginAsEndUser($endUser);

        $this->call('PUT', self::BASE.'/password', ['current_password' => 'wrong', 'password' => 'newsecret1', 'password_confirmation' => 'newsecret1'], $cookies)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['current_password']]]);
        $this->call('PUT', self::BASE.'/password', ['current_password' => 'password', 'password' => 'newsecret1', 'password_confirmation' => 'newsecret1'], $cookies)
            ->assertOk();

        $this->postJson(self::BASE.'/login', ['username' => $endUser->username, 'password' => 'password'])->assertUnauthorized();
        $this->loginAsEndUser($endUser, 'newsecret1');
    }

    public function test_delete_account_removes_everything_after_the_password(): void
    {
        $endUser = EndUser::factory()->create();
        $cookies = $this->loginAsEndUser($endUser);

        $this->call('POST', self::BASE.'/delete-account', ['password' => 'wrong'], $cookies)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('POST', self::BASE.'/delete-account', ['password' => 'password'], $cookies)->assertOk();

        $this->assertDatabaseMissing('end_user', ['id' => $endUser->id]);
    }

    public function test_end_users_and_admins_never_open_each_others_routes(): void
    {
        $userCookies = $this->loginAsEndUser(EndUser::factory()->create());
        $this->call('GET', '/api/admin/credential/me', [], $userCookies)->assertUnauthorized();
        $this->call('GET', '/api/admin/skill/list', [], $userCookies)->assertUnauthorized();

        $adminCookies = $this->loginAsOwner(AdminMst::factory()->create());
        $this->call('GET', self::BASE.'/me', [], $adminCookies)->assertUnauthorized();
    }

    public function test_logging_out_the_end_user_keeps_the_admin_of_the_same_browser(): void
    {
        $admin = AdminMst::factory()->create();
        $endUser = EndUser::factory()->create();
        $cookies = $this->loginAsOwner($admin);
        $cookies = [...$cookies, ...$this->cookiesOf($this->call('POST', self::BASE.'/login', ['username' => $endUser->username, 'password' => 'password'], $cookies)->assertOk())];

        $cookies = [...$cookies, ...$this->cookiesOf($this->call('POST', self::BASE.'/logout', [], $cookies)->assertOk())];

        $this->call('GET', self::BASE.'/me', [], $cookies)->assertUnauthorized();
        $this->call('GET', '/api/admin/credential/me', [], $cookies)->assertOk();
    }
}
