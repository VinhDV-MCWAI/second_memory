<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Remember me" per device (AUTH-05): the device's cookie holds a token whose hash is kept on its
 * admin_session row, so signing that device out (deleting the row) also forgets it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_session', function (Blueprint $table) {
            $table->string('remember_token_hash', 64)->nullable()->comment('sha256 of the remember-me cookie token');
            $table->timestamp('remembered_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('admin_session', function (Blueprint $table) {
            $table->dropColumn(['remember_token_hash', 'remembered_until']);
        });
    }
};
