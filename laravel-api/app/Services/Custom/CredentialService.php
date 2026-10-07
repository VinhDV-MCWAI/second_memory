<?php

declare(strict_types=1);

namespace App\Services\Custom;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Models\Master\AdminMst;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Session (cookie) login for the admin SPA through Sanctum, see ADR-0004.
 */
final class CredentialService
{
    /**
     * @param  array{user_name: string, password: string}  $credentials
     *
     * @throws AuthenticationException
     * @throws ThrottleRequestsException
     */
    public function login(array $credentials, Request $request): AdminMst
    {
        // Sanctum only starts a session for requests coming from the SPA (stateful domains)
        if (! $request->hasSession()) {
            throw new AuthenticationException(Messages::E0401);
        }

        $throttleKey = Str::lower($credentials['user_name']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, CommonVal::LOGIN_MAX_ATTEMPTS)) {
            throw new ThrottleRequestsException(
                Messages::E0429,
                headers: ['Retry-After' => RateLimiter::availableIn($throttleKey)]
            );
        }

        // Same message for an unknown user, a wrong password and a disabled admin (no user enumeration)
        if (! $this->guard()->attempt($credentials)) {
            RateLimiter::hit($throttleKey, CommonVal::LOGIN_LOCK_SECONDS);

            throw new AuthenticationException(Messages::E0401);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        /** @var AdminMst $admin */
        $admin = $this->guard()->user();

        return $admin;
    }

    /**
     * Idempotent: succeeds without a session too, so the SPA can always clear its state.
     */
    public function logout(Request $request): void
    {
        if ($this->guard()->check()) {
            $this->guard()->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    private function guard(): StatefulGuard
    {
        return Auth::guard(CommonVal::ADMIN_GUARD);
    }
}
