<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\Messages;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route permission check for a signed-in admin (runs after auth:sanctum).
 * Reads admin_permission_view on every request, so role changes apply at once.
 * Replaced by owner / viewer Gates in RFC-001 slice 8.
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
        $route = $request->route();
        $allowed = $route !== null && DB::table('admin_permission_view')
            ->where('admin_mst_id', $request->user()?->getAuthIdentifier())
            ->where('type', strtoupper($request->method()))
            ->where('path', trim($route->uri(), '/'))
            ->exists();

        if (! $allowed) {
            throw new AuthorizationException(Messages::E0403);
        }

        return $next($request);
    }
}
