<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_mgmt_hist', function (Blueprint $table) {
            $table->increments('id')->comment('Social history id');
            $table->unsignedInteger('social_mgmt_id')->comment('Social management id');
            $table->string('name', 50)->nullable()->comment('name');
            $table->string('slug', 50)->nullable()->comment('slug');
            $table->string('link', 255)->nullable()->comment('link');
            $table->string('image', 100)->nullable()->comment('image name');
            $table->integer('status')->nullable()->comment('status');
            $table->boolean('is_display')->nullable()->default(false)->comment('display status');
            $table->integer('rank_order')->nullable()->default(0)->comment('rank order');
            $table->integer('action')->comment('action');
            $table->integer('author_id')->comment('Author id');
            $table->timestamp('created_at')->comment('Created time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_mgmt_hist');
    }
};
