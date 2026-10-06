<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_mst', function (Blueprint $table) {
            $table->increments('id')->comment('Role ID');
            $table->string('name', 30)->unique()->comment('Role name');
            $table->string('permission', 50)->comment('Role description');
            $table->boolean('is_active')->default(false)->comment('Role active');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_mst');
    }
};
