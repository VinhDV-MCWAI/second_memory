<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Models\Master\AdminMst;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ADR-0006 backfill step: legacy admin_mst_hist rows are copied once, dual-written rows are skipped.
 */
final class AuditLogBackfillTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_10_07_100008_backfill_audit_log_from_admin_mst_hist.php';

    public function test_backfill_copies_unlinked_history_rows_once(): void
    {
        $admin = AdminMst::factory()->create();
        $author = AdminMst::factory()->create();
        $created = $this->historyRow($admin, 1, ['first_name' => 'Old']);
        $deleted = $this->historyRow($admin, 3, ['first_name' => 'Gone']);
        $alreadyLinked = $this->historyRow($admin, 2, ['first_name' => 'Dual']);
        DB::table('audit_log')->insert([
            'auditable_type' => 'admin', 'auditable_id' => $admin->id, 'event' => 'updated',
            'new_values' => '{"first_name":"Dual"}', 'legacy_hist_id' => $alreadyLinked, 'created_at' => now(),
        ]);

        $this->runBackfill();
        $this->runBackfill(); // idempotent

        $rows = DB::table('audit_log')->where('auditable_id', $admin->id)->orderBy('legacy_hist_id')->get();
        $this->assertCount(3, $rows);

        $fromCreate = $rows->firstWhere('legacy_hist_id', $created);
        $this->assertSame('created', $fromCreate->event);
        $this->assertNull($fromCreate->old_values);
        $values = json_decode($fromCreate->new_values, true);
        $this->assertSame('Old', $values['first_name']);
        $this->assertArrayNotHasKey('password', $values);
        $this->assertSame($author->id, $fromCreate->admin_mst_id);

        $fromDelete = $rows->firstWhere('legacy_hist_id', $deleted);
        $this->assertSame('deleted', $fromDelete->event);
        $this->assertNull($fromDelete->new_values);
        $this->assertSame('Gone', json_decode($fromDelete->old_values, true)['first_name']);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function historyRow(AdminMst $admin, int $action, array $values): int
    {
        return (int) DB::table('admin_mst_hist')->insertGetId([
            'admin_mst_id' => $admin->id,
            'user_name' => $admin->user_name,
            'password' => 'hash-must-not-be-copied',
            'action' => $action,
            'author_id' => AdminMst::where('id', '!=', $admin->id)->latest('id')->value('id'),
            'created_at' => now()->subDay(),
            ...$values,
        ]);
    }

    private function runBackfill(): void
    {
        (require base_path(self::MIGRATION))->up();
    }
}
