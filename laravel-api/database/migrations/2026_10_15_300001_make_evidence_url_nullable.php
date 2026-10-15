<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LEDGER-02: evidence may be files only (a certificate, a report) without a link. EvidenceService
 * still requires a link or at least one file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->string('url', 2048)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rows without a link get an empty string back, so the column can be required again
        DB::table('evidence')->whereNull('url')->update(['url' => '']);
        Schema::table('evidence', function (Blueprint $table) {
            $table->string('url', 2048)->nullable(false)->change();
        });
    }
};
