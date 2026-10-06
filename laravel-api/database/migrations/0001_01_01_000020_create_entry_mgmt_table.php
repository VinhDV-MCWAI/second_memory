<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_mgmt', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->comment('Entry name');
            $table->string('slug', 255)->comment('Entry slug');
            $table->unsignedTinyInteger('status')->default(0)->comment('Entry status');
            $table->boolean('is_display')->default(false)->comment('Display entry');
            $table->unsignedSmallInteger('rank_order')->default(0)->comment('Entry order');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_mgmt');
    }
};
