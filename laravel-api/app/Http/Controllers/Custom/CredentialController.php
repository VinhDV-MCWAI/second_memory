<?php

namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Custom\Credential\LoginRequest;
use App\Services\Custom\CredentialService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class CredentialController extends Controller
{
    public function __construct(
        protected CredentialService $credentialService
    ) {}

    /**
     * Login admin account
     *
     * @throws AuthorizationException
     */
    public function login(LoginRequest $request): array
    {
        return $this->credentialService->login($request);
    }

    /**
     * Refresh token admin account
     *
     * @throws AuthorizationException
     */
    public function refreshToken(Request $request): array
    {
        return $this->credentialService->refreshToken($request);
    }

    /**
     * Logout admin account
     *
     * @throws AuthorizationException
     */
    public function logout(Request $request): array
    {
        return $this->credentialService->logout($request);
    }

    /**
     * Get current authenticated admin user
     */
    public function me(Request $request): array
    {
        return $this->credentialService->me($request);
    }
}
