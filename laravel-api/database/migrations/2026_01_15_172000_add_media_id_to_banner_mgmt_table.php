<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('banner_mgmt', function (Blueprint $table) {
            $table->unsignedBigInteger('media_id')->nullable()->after('is_delete');
            $table->dropColumn(['link', 'image']);
            // No FK constraint as requested
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banner_mgmt', function (Blueprint $table) {
            $table->dropColumn('media_id');
            $table->string('link')->nullable();
            $table->string('image')->nullable();
        });
    }
};
