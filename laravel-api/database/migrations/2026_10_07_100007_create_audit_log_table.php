<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RFC-001 slice 9, expand step (ADR-0006): one append-only audit log for every audited change.
 * `legacy_hist_id` links rows to admin_mst_hist while both are written; dropped in the contract step.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('auditable_type', 50)->comment('Short alias of the audited entity, e.g. admin');
            $table->unsignedBigInteger('auditable_id')->nullable()->comment('Audited row; null for a failed login of an unknown user');
            $table->string('event', 20)->comment('App\Enums\AuditEvent');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedInteger('admin_mst_id')->nullable()->comment('Who did it');
            $table->string('ip_address', 45)->nullable();
            $table->unsignedInteger('legacy_hist_id')->nullable()->unique()->comment('Temporary: admin_mst_hist.id during the migration');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
