<?php

declare(strict_types=1);

use App\Constants\CommonVal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The devices an admin is signed in on (AUTH-04). Sessions stay in Redis; this table only lists them,
 * so an admin can see where they are signed in and end a session from another device.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_session', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admin_mst')->cascadeOnDelete();
            $table->string('session_id')->unique()->comment('Laravel session id (the Redis key); never sent to the browser in the API');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', CommonVal::USER_AGENT_MAX)->nullable();
            $table->timestamp('created_at')->comment('Signed in at');
            $table->timestamp('last_used_at')->comment('Written at most every CommonVal::SESSION_TOUCH_SECONDS');
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_session');
    }
};
