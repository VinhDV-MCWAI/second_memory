<?php

declare(strict_types=1);

use App\Support\Database\ReplaysMigrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RFC-001 slice 9, contract step (ADR-0006): audit_log is the only audit trail now.
 * Refuses to run if a history row was never copied (the backfill ..._100008 must run first).
 */
return new class extends Migration
{
    use ReplaysMigrations;

    public function up(): void
    {
        $notCopied = DB::table('admin_mst_hist')
            ->whereNotIn('id', DB::table('audit_log')->whereNotNull('legacy_hist_id')->select('legacy_hist_id'))
            ->count();
        if ($notCopied > 0) {
            throw new RuntimeException("{$notCopied} admin_mst_hist rows are not in audit_log yet; run the backfill migration first.");
        }

        Schema::dropIfExists('admin_mst_hist');
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropUnique(['legacy_hist_id']);
            $table->dropColumn('legacy_hist_id');
        });
    }

    /**
     * Structure only (as left by ..._100006 and ..._100005); history data comes back from a backup.
     */
    public function down(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->unsignedInteger('legacy_hist_id')->nullable()->unique()->comment('Temporary: admin_mst_hist.id during the migration');
        });

        $this->replay(['0001_01_01_000029_create_admin_mst_hist_table.php']);
        Schema::table('admin_mst_hist', function (Blueprint $table) {
            $table->dropColumn('limit_access');
            $table->string('role', 20)->nullable()->after('is_active')->comment('owner | viewer');
        });
    }
};
