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
        Schema::create('user_mgmt', function (Blueprint $table) {
            $table->increments('id')->comment('User ID');
            $table->string('email', 30)->comment('User email');
            $table->string('user_name', 50)->comment('User name');
            $table->string('password', 100)->comment('User password');
            $table->string('first_name', 20)->comment('User first name');
            $table->string('last_name', 20)->comment('User last name');
            $table->string('address', 100)->nullable()->comment('User address');
            $table->string('phone_number', 20)->nullable()->comment('User phone number');
            $table->string('birth')->nullable()->comment('User birthday');
            $table->integer('gender')->nullable()->comment('User gender');
            $table->integer('status')->nullable()->comment('User status');
            $table->boolean('is_active')->default(false)->comment('User active status');
            $table->string('avatar', 30)->nullable()->comment('User avatar');
            $table->string('email_verified_at')->nullable()->comment('User email verified at');
            $table->string('remember_token', 100)->nullable()->comment('Remember token');
            $table->boolean('is_delete')->default(false)->comment('is deleted');
            $table->string('created_at')->nullable()->comment('User created at');
            $table->string('updated_at')->nullable()->comment('User updated status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_mgmt');
    }
};
