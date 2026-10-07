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
        Schema::create('slider_mgmt_hist', function (Blueprint $table) {
            $table->increments('id')->comment('Slider history id');
            $table->unsignedInteger('slider_mgmt_id')->comment('id');
            $table->string('title', 50)->nullable()->comment('title');
            $table->string('slug', 50)->nullable()->comment('slug');
            $table->string('link', 100)->nullable()->comment('link');
            $table->string('image', 100)->nullable()->comment('image');
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
        Schema::dropIfExists('slider_mgmt_hist');
    }
};
