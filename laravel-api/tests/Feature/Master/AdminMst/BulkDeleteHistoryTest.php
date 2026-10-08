<?php

declare(strict_types=1);

namespace Tests\Feature\Master\AdminMst;

use App\Enums\AuditEvent;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

final class BulkDeleteHistoryTest extends TestCase
{
    use AuthenticatesAdmins;

    private const string URL = 'api/admin/admin-mst/delete';

    public function test_bulk_delete_records_one_audit_row_per_item(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginAsOwner($admin);
        $others = AdminMst::factory()->count(3)->create();

        $this->call('POST', self::URL, ['ids' => $others->pluck('id')->all()], $cookies)->assertStatus(200);

        foreach ($others as $other) {
            $this->assertDatabaseHas('audit_log', [
                'auditable_type' => 'admin',
                'auditable_id' => $other->id,
                'event' => AuditEvent::DELETED->value,
            ]);
        }
    }
}
