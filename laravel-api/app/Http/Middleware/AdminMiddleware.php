<?php

namespace App\Http\Middleware;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Utilities\JsonWebToken;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = $request->cookie('access_token');

        // Check existing access token
        if (! $accessToken) {
            throw new AuthorizationException(Messages::E0401, CommonVal::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = JsonWebToken::decode($accessToken, env('ACCESS_TOKEN_SECRET'));
        } catch (UnexpectedValueException $e) {
            throw new AuthorizationException(Messages::E0401, CommonVal::HTTP_UNAUTHORIZED);
        }

        $credentials = $payload['body'];
        // Check request from member type admin
        if ($credentials['type'] !== CommonVal::ADMIN_TYPE) {
            throw new AuthorizationException(Messages::E0608, CommonVal::HTTP_UNAUTHORIZED);
        }

        /**
         * Check access token had exited
         */
        $parentKey = CommonVal::ADMIN_TYPE.":{$credentials['id']}";
        $tokenKey = $parentKey.":{$accessToken}";
        if (! Redis::exists($tokenKey)) {
            throw new AuthorizationException(Messages::E0609, CommonVal::HTTP_UNAUTHORIZED);
        }

        // Get current route pattern (already normalized by Laravel)
        $method = strtoupper($request->method());
        $currentRoute = trim($request->route()->uri(), '/');

        // Check if this route is in the allowed list
        $permissionTableKey = $parentKey.':'.CommonVal::ADMIN_PERMISSION_TABLE;
        $pathsJson = Redis::hget($permissionTableKey, $method);

        if (! $pathsJson) {
            throw new NotFoundHttpException(Messages::E0404, null, CommonVal::HTTP_UNAUTHORIZED);
        }

        $allowedRoutes = json_decode($pathsJson, true);

        // Simple check: is current route in the allowed list?
        if (! in_array($currentRoute, $allowedRoutes)) {
            throw new AuthorizationException(Messages::E0401, CommonVal::HTTP_UNAUTHORIZED);
        }

        // Set current admin ID for Service consumption
        $request->attributes->set('current_admin_id', $credentials['id']);

        // Set a pseudo user object for broadcasting auth
        // Broadcasting authorization expects $user parameter
        $request->setUserResolver(function () use ($credentials) {
            return (object) ['id' => $credentials['id'], 'type' => $credentials['type']];
        });

        return $next($request);
    }
}
