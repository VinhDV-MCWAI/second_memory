<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_mgmt_hist', function (Blueprint $table) {
            $table->increments('id')->comment('Category id');
            $table->unsignedInteger('category_mgmt_id')->comment('Category management id');
            $table->string('name', 255)->nullable()->comment('name');
            $table->string('slug', 255)->nullable()->comment('slug');
            $table->string('description', 150)->nullable()->comment('description');
            $table->unsignedTinyInteger('status')->nullable()->comment('status');
            $table->boolean('is_display')->nullable()->comment('Display category');
            $table->unsignedSmallInteger('rank_order')->nullable()->comment('order');
            $table->unsignedTinyInteger('action')->comment('action');
            $table->unsignedInteger('author_id')->comment('Author id');
            $table->timestamp('created_at')->comment('Created time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_mgmt_hist');
    }
};
