<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Match user_mgmt.address (100) so history rows are never truncated.
     */
    public function up(): void
    {
        Schema::table('user_mgmt_hist', function (Blueprint $table) {
            $table->string('address', 100)->nullable()->comment('address')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_mgmt_hist', function (Blueprint $table) {
            $table->string('address', 50)->nullable()->comment('address')->change();
        });
    }
};
