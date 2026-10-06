<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_mst', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->comment('Department code');
            $table->string('name', 50)->comment('Department name');
            $table->unsignedTinyInteger('status')->default(0)->comment('Department status');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_mst');
    }
};
