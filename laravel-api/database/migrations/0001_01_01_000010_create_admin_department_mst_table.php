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
        Schema::create('admin_department_mst', function (Blueprint $table) {
            // Foreign key references to admin_mst and department_mst tables
            $table->unsignedInteger('admin_mst_id')->comment('Admin ID');
            $table->unsignedInteger('department_mst_id')->comment('Department ID');

            // Composite primary key
            $table->primary(['admin_mst_id', 'department_mst_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_department_mst');
    }
};
