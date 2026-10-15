<?php

declare(strict_types=1);

namespace App\Services\Custom;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Enums\AuditEvent;
use App\Models\Master\AdminMst;
use App\Services\AuditLogger;
use App\Services\Master\AdminSessionService;
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
    /** audit_log.auditable_type of admins (AdminMstService::$auditableType). */
    private const AUDITABLE_TYPE = 'admin';

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly AdminSessionService $adminSessions,
    ) {}

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

        $userKey = Str::lower($credentials['user_name']).'|'.$request->ip();
        $ipKey = 'login-ip:'.$request->ip(); // no '|', so no user name key can match it
        $this->ensureNotThrottled([
            $userKey => CommonVal::LOGIN_MAX_ATTEMPTS,
            $ipKey => CommonVal::LOGIN_IP_MAX_ATTEMPTS,
        ]);

        // Same message for an unknown user, a wrong password and a disabled admin (no user enumeration)
        if (! $this->guard()->attempt($credentials)) {
            RateLimiter::hit($userKey, CommonVal::LOGIN_LOCK_SECONDS);
            RateLimiter::hit($ipKey, CommonVal::LOGIN_LOCK_SECONDS);
            // No admin id: looking it up would make unknown and known user names distinguishable
            $this->auditLogger->record(self::AUDITABLE_TYPE, null, AuditEvent::LOGIN_FAILED, null, ['user_name' => $credentials['user_name']]);

            throw new AuthenticationException(Messages::E0401);
        }

        // The IP counter is kept: one valid account must not reset a guessing run over other names
        RateLimiter::clear($userKey);
        $request->session()->regenerate();

        /** @var AdminMst $admin */
        $admin = $this->guard()->user();
        $this->adminSessions->start($admin->id, $request);
        $this->auditLogger->record(self::AUDITABLE_TYPE, $admin->id, AuditEvent::LOGGED_IN, actorId: $admin->id);

        return $admin;
    }

    /**
     * Idempotent: succeeds without a session too, so the SPA can always clear its state.
     */
    public function logout(Request $request): void
    {
        if ($this->guard()->check()) {
            $adminId = (int) $this->guard()->id();
            $this->auditLogger->record(self::AUDITABLE_TYPE, $adminId, AuditEvent::LOGGED_OUT, actorId: $adminId);
            $this->guard()->logout();
            if ($request->hasSession()) {
                $this->adminSessions->forget($request->session()->getId());
            }
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    /**
     * @param  array<string, int>  $limits  throttle key => max failures
     *
     * @throws ThrottleRequestsException
     */
    private function ensureNotThrottled(array $limits): void
    {
        foreach ($limits as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                throw new ThrottleRequestsException(
                    Messages::E0429,
                    headers: ['Retry-After' => RateLimiter::availableIn($key)]
                );
            }
        }
    }

    private function guard(): StatefulGuard
    {
        return Auth::guard(CommonVal::ADMIN_GUARD);
    }
}
