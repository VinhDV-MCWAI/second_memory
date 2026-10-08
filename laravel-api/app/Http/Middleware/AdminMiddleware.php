<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\CommonVal;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role check for a signed-in admin (runs after auth:sanctum, ADR-0005):
 * every admin may read, only an owner may change data (Gate `write`).
 */
final class AdminMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     *
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            Gate::authorize(CommonVal::GATE_WRITE);
        }

        return $next($request);
    }
}
