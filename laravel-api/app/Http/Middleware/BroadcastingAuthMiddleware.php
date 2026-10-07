<?php

namespace App\Http\Middleware;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Utilities\JsonWebToken;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * Broadcasting Authentication Middleware
 *
 * Purpose: Authenticate WebSocket channel subscription requests
 * - Verify JWT token from cookie
 * - Extract admin ID from token
 * - Set user resolver for Laravel Broadcasting authorization
 * - Does NOT check route permissions (broadcasting auth is authorized in channels.php)
 */
class BroadcastingAuthMiddleware
{
    /**
     * Handle an incoming request for broadcasting authentication.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('[BroadcastingAuthMiddleware] Request received', [
            'path' => $request->path(),
            'method' => $request->method(),
            'has_cookie' => $request->hasCookie('access_token'),
        ]);

        $accessToken = $request->cookie('access_token');

        // Check existing access token
        if (! $accessToken) {
            Log::warning('[BroadcastingAuthMiddleware] No access token in cookie');
            throw new AuthorizationException(Messages::E0401, CommonVal::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = JsonWebToken::decode($accessToken, env('ACCESS_TOKEN_SECRET'));
        } catch (UnexpectedValueException) {
            throw new AuthorizationException(Messages::E0401, CommonVal::HTTP_UNAUTHORIZED);
        }

        $credentials = $payload['body'];

        // Check request from member type admin
        if ($credentials['type'] !== CommonVal::ADMIN_TYPE) {
            throw new AuthorizationException(Messages::E0608, CommonVal::HTTP_UNAUTHORIZED);
        }

        /**
         * Check access token is still valid in Redis
         */
        $parentKey = CommonVal::ADMIN_TYPE.":{$credentials['id']}";
        $tokenKey = $parentKey.":{$accessToken}";

        if (! Redis::exists($tokenKey)) {
            throw new AuthorizationException(Messages::E0609, CommonVal::HTTP_UNAUTHORIZED);
        }

        // Set current admin ID for downstream usage
        $request->attributes->set('current_admin_id', $credentials['id']);

        // Set a user resolver for Laravel Broadcasting authorization
        // Broadcasting authorization in channels.php expects $user parameter
        // This resolver provides the authenticated user object
        $request->setUserResolver(fn () => (object) [
            'id' => $credentials['id'],
            'type' => $credentials['type'],
        ]);

        Log::info('[BroadcastingAuthMiddleware] Auth successful', [
            'user_id' => $credentials['id'],
            'user_type' => $credentials['type'],
        ]);

        return $next($request);
    }
}
