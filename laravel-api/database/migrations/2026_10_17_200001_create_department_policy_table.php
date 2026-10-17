<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Folders of the file manager given to departments (ADR-0017). A folder with a row here is locked:
 * only members of one of its departments (and owners) may use it and everything below it. Kept by
 * folder id, so renaming or moving the folder keeps the lock; deleting the row for good drops it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_policy', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained('department')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media_mgmt')->cascadeOnDelete();
            $table->primary(['department_id', 'media_id']);
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_policy');
    }
};
