<?php

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
        Schema::create('department_management_mst', function (Blueprint $table) {
            // Foreign key references to department_mst and policy_department_mst tables
            $table->unsignedInteger('department_mst_id')->comment('Department ID');
            $table->unsignedInteger('policy_department_mst_id')->comment('Policy Department ID');

            // Composite primary key
            $table->primary(['department_mst_id', 'policy_department_mst_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_management_mst');
    }
};
