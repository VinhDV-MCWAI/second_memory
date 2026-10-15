<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\PermissionConst;
use App\Models\Master\AdminMst;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permission check for a signed-in admin (runs after auth:sanctum, ADR-0013): the route's name is the
 * permission, granted through the admin's roles. The owner role passes every check (Gate::before in
 * AppServiceProvider). A route without a name is refused, so a forgotten name fails closed.
 *
 * API tokens (ADR-0010) only open routes that name their abilities, e.g. `auth.admin:evidence:import`;
 * on every other admin route a token gets 403, so a leaked importer token cannot read or change anything else.
 * A session login (Sanctum's transient token) passes the ability check.
 */
final class AdminMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     *
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $admin = $request->user();
        if (! $admin instanceof AdminMst) {
            throw new AuthorizationException;
        }
        if ($admin->currentAccessToken() instanceof PersonalAccessToken) {
            $this->authorizeToken($admin->currentAccessToken(), $abilities);
        }

        $permission = $request->route()?->getName();
        if ($permission === null) {
            throw new AuthorizationException;
        }
        if (! in_array($permission, PermissionConst::ALWAYS_ALLOWED, true) && $admin->cannot($permission)) {
            throw new AuthorizationException;
        }

        return $next($request);
    }

    /**
     * @param  list<string>  $abilities
     *
     * @throws AuthorizationException
     */
    private function authorizeToken(PersonalAccessToken $token, array $abilities): void
    {
        if ($abilities === []) {
            throw new AuthorizationException;
        }
        foreach ($abilities as $ability) {
            if ($token->cant($ability)) {
                throw new AuthorizationException;
            }
        }
    }
}
