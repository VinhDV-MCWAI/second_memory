<?php

declare(strict_types=1);

namespace Tests\Feature\Management\SocialMgmt;

use App\Enums\ActionType;
use App\Models\Management\SocialMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class BulkDeleteHistoryTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/social-mgmt/delete';

    public function test_bulk_delete_records_one_history_row_per_item(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['POST', self::URL]]);
        $socials = SocialMgmt::factory()->count(3)->create();

        $this->call('POST', self::URL, ['ids' => $socials->pluck('id')->all()], $cookies)->assertStatus(200);

        foreach ($socials as $social) {
            $this->assertDatabaseHas('social_mgmt_hist', [
                'social_mgmt_id' => $social->id,
                'action' => ActionType::DELETE->value,
            ]);
        }
    }
}
