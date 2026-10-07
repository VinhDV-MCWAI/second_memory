<?php

declare(strict_types=1);

namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Custom\Credential\LoginRequest;
use App\Http\Resources\Master\AdminMstResource;
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
     * Log in: starts the session. Call `GET /api/sanctum/csrf-cookie` first.
     *
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     * @throws ThrottleRequestsException
     */
    public function login(LoginRequest $request): array
    {
        /** @var array{user_name: string, password: string} $credentials */
        $credentials = $request->validated();

        return (new AdminMstResource($this->credentialService->login($credentials, $request)))->resolve($request);
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
        return (new AdminMstResource($request->user()))->resolve($request);
    }
}
