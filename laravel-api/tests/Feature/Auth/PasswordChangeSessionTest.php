<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * A new password ends every other session of that admin (Sanctum AuthenticateSession compares
 * the password hash kept in the session), so a leaked password can be cut off by changing it.
 */
final class PasswordChangeSessionTest extends TestCase
{
    use AuthenticatesAdmins;

    private const string ME_URL = '/api/admin/credential/me';

    private const string NEW_PASSWORD = 'NewPassword123';

    public function test_changing_my_password_ends_my_other_sessions_and_keeps_this_one(): void
    {
        $owner = AdminMst::factory()->create();
        $thisDevice = $this->loginAsOwner($owner);
        $otherDevice = $this->loginAs($owner);

        $this->call('PUT', $this->updateUrl($owner), $this->payload($owner, self::NEW_PASSWORD), $thisDevice)->assertOk();

        $this->call('GET', self::ME_URL, [], $otherDevice)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->call('GET', self::ME_URL, [], $thisDevice)->assertOk();
        $this->call('GET', self::ME_URL, [], $this->loginAs($owner, self::NEW_PASSWORD))->assertOk();
    }

    public function test_changing_another_admins_password_ends_their_sessions(): void
    {
        $ownerDevice = $this->loginAsOwner(AdminMst::factory()->create());
        $viewer = AdminMst::factory()->create();
        $viewerDevice = $this->loginAs($viewer);
        $this->call('GET', self::ME_URL, [], $viewerDevice)->assertOk();

        $this->call('PUT', $this->updateUrl($viewer), $this->payload($viewer, self::NEW_PASSWORD), $ownerDevice)->assertOk();

        $this->call('GET', self::ME_URL, [], $viewerDevice)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->call('GET', self::ME_URL, [], $ownerDevice)->assertOk();
    }

    public function test_saving_without_a_new_password_keeps_other_sessions(): void
    {
        $owner = AdminMst::factory()->create();
        $thisDevice = $this->loginAsOwner($owner);
        $otherDevice = $this->loginAs($owner);

        $this->call('PUT', $this->updateUrl($owner), $this->payload($owner, null), $thisDevice)->assertOk();

        $this->call('GET', self::ME_URL, [], $otherDevice)->assertOk();
    }

    private function updateUrl(AdminMst $admin): string
    {
        return "/api/admin/admin-mst/update/{$admin->id}";
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(AdminMst $admin, ?string $password): array
    {
        return [
            'email' => $admin->email,
            'user_name' => $admin->user_name,
            'password' => $password,
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'gender' => $admin->gender,
            'status' => $admin->status,
            'is_active' => $admin->is_active,
        ];
    }
}
