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
        Schema::create('token_mst', function (Blueprint $table) {
            $table->increments('id')->comment('Token ID');
            $table->string('token_hash')->nullable(false)->comment('Token hash');
            $table->unsignedInteger('account_id')->nullable(false)->comment('Account ID');
            $table->string('device_name')->nullable()->comment('Device name');
            $table->string('ip_address')->nullable()->comment('IP address');
            $table->timestamp('expired_at')->nullable()->comment('Expiration time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_mst');
    }
};
