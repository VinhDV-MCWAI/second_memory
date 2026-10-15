<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The public Skill Ledger never shows internal ids (RFC-002 §4.3): an attachment is linked by a random
 * key instead, set by the database so re-saving an evidence keeps its links.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence_media', function (Blueprint $table) {
            $table->uuid('public_id')->default(DB::raw('gen_random_uuid()'))->unique()->comment('Key of public/files/{key}');
        });
    }

    public function down(): void
    {
        Schema::table('evidence_media', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
};
