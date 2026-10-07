<?php

declare(strict_types=1);

namespace Tests\Feature\Master\AdminMst;

use App\Enums\ActionType;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class BulkDeleteHistoryTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/admin-mst/delete';

    public function test_bulk_delete_records_one_history_row_per_item(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['POST', self::URL]]);
        $others = AdminMst::factory()->count(3)->create();

        $this->call('POST', self::URL, ['ids' => $others->pluck('id')->all()], $cookies)->assertStatus(200);

        foreach ($others as $other) {
            $this->assertDatabaseHas('admin_mst_hist', [
                'admin_mst_id' => $other->id,
                'action' => ActionType::DELETE->value,
            ]);
        }
    }
}
