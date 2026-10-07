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
        Schema::create('api_role_mst', function (Blueprint $table) {
            // Foreign key references to api_mst and role_mst tables
            $table->unsignedInteger('api_mst_id')->comment('API ID');
            $table->unsignedInteger('role_mst_id')->comment('Role ID');

            // Composite primary key
            $table->primary(['api_mst_id', 'role_mst_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_role_mst');
    }
};
