<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Constants\CommonVal;
use App\Constants\EndUserConst;
use App\Constants\Messages;
use App\Models\User\EndUser;
use App\Repositories\Master\EndUserRepository;
use App\Services\System\RegistrationSetting;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Sign-up and session login of end users (ADR-0020). Same session cookie as the admin SPA, but
 * the `user` guard only: logging out leaves an admin signed in on the same browser.
 */
final class EndUserAuthService
{
    public function __construct(
        private readonly EndUserRepository $endUsers,
        private readonly RegistrationSetting $registration,
    ) {}

    /**
     * @param  array{username: string, display_name: string, password: string}  $payload
     *
     * @throws AuthenticationException without a session (request not from the public site)
     * @throws AccessDeniedHttpException when the owner closed registration
     */
    public function register(array $payload, Request $request): EndUser
    {
        $this->ensureSession($request);
        if (! $this->registration->isOpen()) {
            throw new AccessDeniedHttpException(Messages::E0046);
        }

        $endUser = $this->endUsers->create($payload['username'], $payload['display_name'], $payload['password']);
        $this->signIn($endUser, $request);

        return $endUser;
    }

    /**
     * @param  array{username: string, password: string}  $credentials
     *
     * @throws AuthenticationException
     * @throws ThrottleRequestsException
     */
    public function login(array $credentials, Request $request): EndUser
    {
        $this->ensureSession($request);
        $username = EndUserRepository::normalizeUsername($credentials['username']);
        $usernameKey = 'user-login:'.$username.'|'.$request->ip();
        $ipKey = 'user-login-ip:'.$request->ip();
        foreach ([$usernameKey => CommonVal::LOGIN_MAX_ATTEMPTS, $ipKey => CommonVal::LOGIN_IP_MAX_ATTEMPTS] as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                throw new ThrottleRequestsException(Messages::E0429, headers: ['Retry-After' => RateLimiter::availableIn($key)]);
            }
        }

        // Same answer for an unknown username, a wrong password and a locked account
        if (! $this->guard()->attempt(['username' => $username, 'password' => $credentials['password']])) {
            RateLimiter::hit($usernameKey, CommonVal::LOGIN_LOCK_SECONDS);
            RateLimiter::hit($ipKey, CommonVal::LOGIN_LOCK_SECONDS);

            throw new AuthenticationException(Messages::E0401);
        }
        RateLimiter::clear($usernameKey);

        /** @var EndUser $endUser */
        $endUser = $this->guard()->user();
        $this->started($endUser, $request);

        return $endUser;
    }

    /**
     * Idempotent. Only the `user` guard: the session (and an admin login in it) stays.
     */
    public function logout(Request $request): void
    {
        $this->guard()->logout();
        if ($request->hasSession()) {
            $request->session()->regenerateToken();
        }
    }

    public function changePassword(EndUser $endUser, string $password, Request $request): void
    {
        $endUser->forceFill(['password' => $password])->save();
        $request->session()->regenerate();
    }

    public function deleteAccount(EndUser $endUser, Request $request): void
    {
        // Saved items go with the row (foreign key cascade)
        $endUser->delete();
        $this->logout($request);
    }

    private function signIn(EndUser $endUser, Request $request): void
    {
        $this->guard()->login($endUser);
        $this->started($endUser, $request);
    }

    private function started(EndUser $endUser, Request $request): void
    {
        $request->session()->regenerate();
        $endUser->forceFill(['last_login_at' => now()])->save();
    }

    /**
     * @throws AuthenticationException
     */
    private function ensureSession(Request $request): void
    {
        // Sanctum only starts a session for requests from the stateful domains (the public site)
        if (! $request->hasSession()) {
            throw new AuthenticationException(Messages::E0401);
        }
    }

    private function guard(): StatefulGuard
    {
        return Auth::guard(EndUserConst::GUARD);
    }
}
