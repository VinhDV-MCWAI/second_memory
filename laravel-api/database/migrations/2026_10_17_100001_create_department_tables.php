<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Departments and their members (ADR-0017). An admin may belong to several departments;
 * deleting a department only removes the memberships.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('admin_mst')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admin_mst')->nullOnDelete();
            $table->timestamps();
        });
        DB::statement('CREATE UNIQUE INDEX department_code_lower_unique ON department (lower(code))');

        Schema::create('admin_department', function (Blueprint $table) {
            $table->foreignId('admin_id')->constrained('admin_mst')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('department')->cascadeOnDelete();
            $table->primary(['admin_id', 'department_id']);
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_department');
        Schema::dropIfExists('department');
    }
};
