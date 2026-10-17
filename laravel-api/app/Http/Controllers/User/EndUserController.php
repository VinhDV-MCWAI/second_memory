<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\DeleteAccountRequest;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Resources\User\CurrentEndUserResource;
use App\Models\User\EndUser;
use App\Services\System\RegistrationSetting;
use App\Services\User\EndUserAuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Accounts of the public site (ADR-0020). Call `GET /api/sanctum/csrf-cookie` first.
 */
final class EndUserController extends Controller
{
    public function __construct(
        private readonly EndUserAuthService $auth,
        private readonly RegistrationSetting $registration,
    ) {}

    /**
     * Whether visitors may create an account.
     *
     * @return array{open: bool}
     */
    public function registration(): array
    {
        return ['open' => $this->registration->isOpen()];
    }

    /**
     * Create an account and sign in.
     *
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     * @throws AccessDeniedHttpException when registration is closed
     */
    public function register(RegisterRequest $request): array
    {
        /** @var array{email: string, display_name: string, password: string} $payload */
        $payload = $request->safe()->only(['email', 'display_name', 'password']);

        return (new CurrentEndUserResource($this->auth->register($payload, $request)))->toArray($request);
    }

    /**
     * Sign in.
     *
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     * @throws ThrottleRequestsException
     */
    public function login(LoginRequest $request): array
    {
        /** @var array{email: string, password: string} $credentials */
        $credentials = $request->validated();

        return (new CurrentEndUserResource($this->auth->login($credentials, $request)))->toArray($request);
    }

    /**
     * Sign out (also succeeds without a session). An admin signed in on the same browser stays signed in.
     */
    public function logout(Request $request): void
    {
        $this->auth->logout($request);
    }

    /**
     * The signed-in end user.
     *
     * @return array<string, mixed>
     */
    public function me(Request $request): array
    {
        return (new CurrentEndUserResource($this->current($request)))->toArray($request);
    }

    public function password(ChangePasswordRequest $request): void
    {
        $this->auth->changePassword($this->current($request), (string) $request->validated('password'), $request);
    }

    /**
     * Delete the account and everything it saved, then sign out.
     */
    public function deleteAccount(DeleteAccountRequest $request): void
    {
        $this->auth->deleteAccount($this->current($request), $request);
    }

    private function current(Request $request): EndUser
    {
        /** @var EndUser */
        return $request->user();
    }
}
