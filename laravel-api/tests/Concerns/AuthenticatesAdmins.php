<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use Illuminate\Testing\TestResponse;

/**
 * Logs admins in through the real login endpoint (Sanctum session, ADR-0004).
 */
trait AuthenticatesAdmins
{
    /**
     * Make the admin an owner (may change data, ADR-0005), log in and return the cookies.
     *
     * @return array<string, string>
     */
    protected function loginAsOwner(AdminMst $admin): array
    {
        $admin->update(['role' => AdminRole::OWNER]);

        return $this->loginAs($admin);
    }

    /**
     * Log in through the API and return the response cookies (session + XSRF-TOKEN).
     *
     * @return array<string, string>
     */
    protected function loginAs(AdminMst $admin, string $password = 'password'): array
    {
        $response = $this->postJson('/api/admin/credential/login', [
            'user_name' => $admin->user_name,
            'password' => $password,
        ]);

        return $this->cookiesOf($response);
    }

    /**
     * @return array<string, string>
     */
    protected function cookiesOf(TestResponse $response): array
    {
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = (string) $cookie->getValue();
        }

        return $cookies;
    }
}
