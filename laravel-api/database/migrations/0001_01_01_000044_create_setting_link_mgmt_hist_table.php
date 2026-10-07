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
        Schema::create('setting_link_mgmt_hist', function (Blueprint $table) {
            $table->increments('id')->comment('Setting link history id');
            $table->unsignedInteger('setting_link_mgmt_id')->comment('Setting link id');
            $table->string('key', 30)->nullable()->comment('key');
            $table->string('value', 100)->nullable()->comment('value');
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
        Schema::dropIfExists('setting_link_mgmt_hist');
    }
};
