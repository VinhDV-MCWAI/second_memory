<?php

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
        Schema::create('entry_mgmt_hist', function (Blueprint $table) {
            $table->increments('id')->comment('Entry id');
            $table->unsignedInteger('entry_mgmt_id')->comment('Entry management id');
            $table->string('name', 255)->nullable()->comment('name');
            $table->string('slug', 255)->nullable()->comment('slug');
            $table->unsignedTinyInteger('status')->nullable()->comment('status');
            $table->boolean('is_display')->nullable()->comment('Display entry');
            $table->unsignedSmallInteger('rank_order')->nullable()->comment('order');
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
        Schema::dropIfExists('entry_mgmt_hist');
    }
};
