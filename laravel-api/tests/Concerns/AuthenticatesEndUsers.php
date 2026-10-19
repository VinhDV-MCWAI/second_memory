<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\User\EndUser;

/**
 * Signs end users in through the real endpoint (ADR-0020). Needs AuthenticatesAdmins::cookiesOf.
 */
trait AuthenticatesEndUsers
{
    /**
     * @return array<string, string> the response cookies (session + XSRF-TOKEN)
     */
    protected function loginAsEndUser(EndUser $endUser, string $password = 'password'): array
    {
        return $this->cookiesOf($this->postJson('/api/user/login', ['username' => $endUser->username, 'password' => $password])->assertOk());
    }
}
