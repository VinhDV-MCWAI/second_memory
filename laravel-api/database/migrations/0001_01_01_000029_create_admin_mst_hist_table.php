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
        Schema::create('admin_mst_hist', function (Blueprint $table) {
            $table->increments('id')->comment('admin history id');
            $table->unsignedInteger('admin_mst_id')->comment('Admin id');
            $table->string('email', 30)->nullable()->comment('email');
            $table->string('user_name', 50)->nullable()->comment('user name');
            $table->string('password', 100)->nullable()->comment('password');
            $table->string('first_name', 20)->nullable()->comment('first name');
            $table->string('last_name', 20)->nullable()->comment('last name');
            $table->string('address', 100)->nullable()->comment('address');
            $table->string('phone_number', 20)->nullable()->comment('phone number');
            $table->timestamp('birth')->nullable()->comment('birth');
            $table->unsignedTinyInteger('gender')->nullable()->comment('gender');
            $table->unsignedTinyInteger('status')->nullable()->comment('status');
            $table->boolean('is_active')->nullable()->comment('active');
            $table->string('avatar', 30)->nullable()->comment('avatar name');
            $table->timestamp('email_verified_at')->nullable()->comment('Verified email time');
            $table->integer('limit_access')->default(0)->comment('Limit access when login fails 5 times');
            $table->string('remember_token', 100)->nullable()->comment('remember token');
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
        Schema::dropIfExists('admin_mst_hist');
    }
};
