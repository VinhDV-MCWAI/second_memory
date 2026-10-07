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
        Schema::create('api_mst_hist', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('api_mst_id')->comment('ApiMst id');
            $table->unsignedTinyInteger('type')->nullable()->comment('type');
            $table->string('name', 50)->nullable()->comment('name');
            $table->string('path', 100)->nullable()->comment('path');
            $table->unsignedTinyInteger('is_active')->nullable()->comment('status');
            $table->unsignedInteger('feature_mst_id')->comment('FeatureMst id');
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
        Schema::dropIfExists('api_mst_hist');
    }
};
