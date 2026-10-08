<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RFC-001 slice 9, backfill step (ADR-0006): copies admin_mst_hist rows that are not in
 * audit_log yet (no row with their legacy_hist_id). Idempotent; rows written by the
 * dual-write are already linked and skipped.
 *
 * A history row is a snapshot of the admin after the change, so: create -> new_values,
 * update -> new_values (the old values were never stored), delete -> old_values.
 */
return new class extends Migration
{
    private const EVENTS = [1 => 'created', 2 => 'updated', 3 => 'deleted'];

    /** History columns that are audit metadata or secrets, not admin data. */
    private const NOT_SNAPSHOT = ['id', 'admin_mst_id', 'action', 'author_id', 'created_at', 'password', 'remember_token', 'limit_access'];

    public function up(): void
    {
        DB::table('admin_mst_hist')
            ->whereNotIn('id', DB::table('audit_log')->whereNotNull('legacy_hist_id')->select('legacy_hist_id'))
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                DB::table('audit_log')->insert($rows->map(function (object $row): array {
                    $event = self::EVENTS[(int) $row->action] ?? 'updated';
                    $snapshot = json_encode(array_diff_key((array) $row, array_flip(self::NOT_SNAPSHOT)));

                    return [
                        'auditable_type' => 'admin',
                        'auditable_id' => $row->admin_mst_id,
                        'event' => $event,
                        'old_values' => $event === 'deleted' ? $snapshot : null,
                        'new_values' => $event === 'deleted' ? null : $snapshot,
                        'admin_mst_id' => $row->author_id,
                        'ip_address' => null,
                        'legacy_hist_id' => $row->id,
                        'created_at' => $row->created_at,
                    ];
                })->all());
            });
    }

    /**
     * Forward-only data copy: the copied rows are valid audit history. Rolling back the
     * expand migration (..._100007) drops audit_log with everything in it.
     */
    public function down(): void {}
};
