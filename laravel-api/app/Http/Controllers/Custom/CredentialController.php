<?php

declare(strict_types=1);

namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Custom\Credential\LoginRequest;
use App\Http\Resources\Master\CurrentAdminResource;
use App\Services\Custom\CredentialService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

final class CredentialController extends Controller
{
    public function __construct(
        private readonly CredentialService $credentialService
    ) {}

    /**
     * Log in: starts the session. Call `GET /api/sanctum/csrf-cookie` first. `remember` keeps this device signed in for 30 days.
     *
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     * @throws ThrottleRequestsException
     */
    public function login(LoginRequest $request): array
    {
        /** @var array{user_name: string, password: string} $credentials */
        $credentials = $request->safe()->only(['user_name', 'password']);
        $admin = $this->credentialService->login($credentials, $request->boolean('remember'), $request);

        return (new CurrentAdminResource($admin))->toArray($request);
    }

    /**
     * Log out: ends the session (also succeeds without one).
     */
    public function logout(Request $request): void
    {
        $this->credentialService->logout($request);
    }

    /**
     * The signed-in admin.
     *
     * @return array<string, mixed>
     */
    public function me(Request $request): array
    {
        return (new CurrentAdminResource($request->user()))->toArray($request);
    }
}
