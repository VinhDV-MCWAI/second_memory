<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_mgmt', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->comment('Category name');
            $table->string('slug', 255)->comment('Category slug');
            $table->string('description', 150)->nullable()->comment('Category description');
            $table->unsignedTinyInteger('status')->default(0)->comment('Category status');
            $table->boolean('is_display')->default(false)->comment('Display category');
            $table->unsignedSmallInteger('rank_order')->default(0)->comment('Category order');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_mgmt');
    }
};
