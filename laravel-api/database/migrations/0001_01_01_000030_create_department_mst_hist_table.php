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
        Schema::create('department_mst_hist', function (Blueprint $table) {
            $table->increments('id')->comment('Department history id');
            $table->unsignedInteger('department_mst_id')->comment('Department id');
            $table->string('code', 50)->nullable()->comment('code');
            $table->string('name', 50)->nullable()->comment('name');
            $table->unsignedTinyInteger('status')->nullable()->comment('status');
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
        Schema::dropIfExists('department_mst_hist');
    }
};
