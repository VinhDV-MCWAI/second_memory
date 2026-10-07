<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_link_mgmt', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key', 30)->comment('Setting link key');
            $table->string('value', 100)->comment('Setting link value');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_link_mgmt');
    }
};
