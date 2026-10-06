<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slider_mgmt', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 50)->comment('Slider title');
            $table->string('slug', 50)->comment('Slider slug');
            $table->string('link', 100)->comment('Slider path image');
            $table->string('image', 100)->comment('Slider image name');
            $table->unsignedTinyInteger('status')->default(0)->comment('Slider image');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slider_mgmt');
    }
};
