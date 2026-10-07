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
        Schema::create('role_mst_hist', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('role_mst_id')->comment('role_id');
            $table->string('name', 30)->nullable()->comment('name');
            $table->string('permission', 50)->nullable()->comment('description');
            $table->boolean('is_active')->nullable()->comment('active');
            $table->unsignedTinyInteger('action')->comment('action');
            $table->unsignedInteger('author_id')->comment('Author id');
            $table->timestamp('created_at')->comment('Created time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_mst_hist');
    }
};
