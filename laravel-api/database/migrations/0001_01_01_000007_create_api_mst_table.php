<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_mst', function (Blueprint $table) {
            $table->increments('id')->comment('Api ID');
            $table->unsignedTinyInteger('type')->default(0)->comment('Api type');
            $table->string('name', 50)->comment('Api name');
            $table->string('path', 100)->comment('Api path');
            $table->boolean('is_active')->default(false)->comment('Api status');
            $table->unsignedInteger('feature_mst_id')->comment('Feature ID');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_mst');
    }
};
