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
        Schema::create('social_mgmt', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('Social name');
            $table->string('slug', 50)->comment('Social slug');
            $table->string('link', 255)->comment('Social link');
            $table->string('image', 100)->comment('Social image');
            $table->integer('status')->comment('Social status');
            $table->boolean('is_display')->comment('Social is display');
            $table->integer('rank_order')->comment('Social rank order');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_mgmt');
    }
};
