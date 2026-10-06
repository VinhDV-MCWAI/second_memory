<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_mgmt_hist', function (Blueprint $table) {
            $table->increments('id')->comment('User mgmt hist id');
            $table->unsignedInteger('user_mgmt_id')->comment('User management id');
            $table->string('email', 30)->nullable()->comment('email');
            $table->string('user_name', 50)->nullable()->comment('name');
            $table->string('password', 100)->nullable()->comment('password');
            $table->string('first_name', 20)->nullable()->comment('First name');
            $table->string('last_name', 20)->nullable()->comment('Last name');
            $table->string('address', 50)->nullable()->comment('address');
            $table->string('phone_number', 20)->nullable()->comment('phone number');
            $table->timestamp('birth')->nullable()->comment('birth');
            $table->unsignedTinyInteger('gender')->nullable()->comment('gender');
            $table->unsignedTinyInteger('status')->nullable()->comment('status');
            $table->boolean('is_active')->nullable()->comment('active');
            $table->string('avatar', 30)->nullable()->comment('avatar name');
            $table->timestamp('email_verified_at')->nullable()->comment('Verified email time');
            $table->string('remember_token', 100)->nullable()->comment('Remember token');
            $table->unsignedTinyInteger('action')->comment('action');
            $table->unsignedInteger('author_id')->comment('Author id');
            $table->timestamp('created_at')->comment('Created time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_mgmt_hist');
    }
};
