<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Models\Master\AdminMst;
use App\Services\Master\AdminSessionService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the sessions screen (AUTH-04) up to date and logs out a session ended from another device.
 * Runs after auth:sanctum; API tokens (ADR-0010) have no session and are skipped.
 */
final class TrackAdminSession
{
    public function __construct(private readonly AdminSessionService $sessions) {}

    /**
     * @param  Closure(Request): (Response)  $next
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();
        if ($admin instanceof AdminMst && $admin->currentAccessToken() instanceof TransientToken && $request->hasSession()
            && ! $this->sessions->track($admin->id, $request)) {
            Auth::guard(CommonVal::ADMIN_GUARD)->logout();
            $request->session()->invalidate();

            throw new AuthenticationException(Messages::E0401);
        }

        return $next($request);
    }
}
