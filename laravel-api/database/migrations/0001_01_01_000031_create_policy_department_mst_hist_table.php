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
        Schema::create('policy_department_mst_hist', function (Blueprint $table) {
            $table->increments('id')->comment('Policy department history id');
            $table->unsignedInteger('policy_department_mst_id')->comment('Policy department id');
            $table->string('table_name', 20)->nullable()->comment('table name');
            $table->unsignedInteger('row_id')->nullable()->comment('Row id');
            $table->unsignedTinyInteger('action')->comment('action');
            $table->unsignedInteger('author_id')->comment('Author id');
            $table->timestamp('created_at')->comment('Created time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_department_mst_hist');
    }
};
