<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0014: the old unique index never fired (workspace_id is always null, and PostgreSQL treats nulls
 * as distinct) and blocked re-using a deleted name. One live item per path, ignoring case; rows still
 * uploading (status 0) do not hold their name until the upload completes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_mgmt', function (Blueprint $table) {
            $table->dropUnique('unique_virtual_path');
            $table->integer('upload_status')->default(0)->comment('0: Uploading, 1: Processing, 2: Completed, 3: Failed')->change();
        });

        DB::statement('CREATE UNIQUE INDEX media_mgmt_live_path_unique ON media_mgmt (lower(virtual_path)) WHERE is_delete = false AND upload_status <> 0');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS media_mgmt_live_path_unique');

        Schema::table('media_mgmt', function (Blueprint $table) {
            $table->integer('upload_status')->default(1)->comment('1: Processing, 2: Completed, 3: Failed')->change();
            $table->unique(['workspace_id', 'virtual_path', 'is_delete'], 'unique_virtual_path');
        });
    }
};
