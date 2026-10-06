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
        Schema::create('entry_description_mgmt', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 100)->comment('Title entry');
            $table->string('summary', 255)->comment('Summary entry');
            $table->json('article')->nullable()->comment('Article entry in JSON format');
            $table->unsignedTinyInteger('status')->default(0)->comment('Entry status');
            $table->boolean('is_display')->default(false)->comment('Display entry');
            $table->unsignedSmallInteger('rank_order')->default(0)->comment('Rank order');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_description_mgmt');
    }
};
