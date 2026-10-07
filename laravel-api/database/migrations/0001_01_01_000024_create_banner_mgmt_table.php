<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_mgmt', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 50)->comment('Banner title');
            $table->string('slug', 50)->comment('Banner slug');
            $table->string('description', 255)->comment('Banner description');
            $table->string('link', 100)->comment('Banner path image');
            $table->string('image', 100)->comment('Banner image name');
            $table->string('position', 50)->comment('Banner display position');
            $table->unsignedTinyInteger('status')->default(0)->comment('Banner status');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_mgmt');
    }
};
