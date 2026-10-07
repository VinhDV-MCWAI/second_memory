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
        Schema::create('policy_department_mst', function (Blueprint $table) {
            $table->increments('id')->comment('Policy department ID');
            $table->string('table_name', 20)->comment('Table name');
            $table->integer('row_id')->comment('Row id');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_department_mst');
    }
};
