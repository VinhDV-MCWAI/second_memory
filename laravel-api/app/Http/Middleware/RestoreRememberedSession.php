<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\CommonVal;
use App\Services\Master\AdminSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Remember me" (AUTH-05): a remembered device whose session has expired is signed in again before
 * auth:sanctum looks at the session. A cookie that no longer counts is dropped and the request stays a guest.
 */
final class RestoreRememberedSession
{
    public function __construct(private readonly AdminSessionService $sessions) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->cookies->has(CommonVal::REMEMBER_COOKIE)
            && ! Auth::guard(CommonVal::ADMIN_GUARD)->check()) {
            $this->sessions->restore($request);
        }

        return $next($request);
    }
}
