<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role check for a signed-in admin (runs after auth:sanctum, ADR-0005):
 * every admin may read, only an owner may change data (Gate `write`).
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
        if ($admin instanceof AdminMst && $admin->currentAccessToken() instanceof PersonalAccessToken) {
            $this->authorizeToken($admin->currentAccessToken(), $abilities);
        }

        if (! $request->isMethodSafe()) {
            Gate::authorize(CommonVal::GATE_WRITE);
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
