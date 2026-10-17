<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * End-user accounts of the public site (ADR-0020): their own table and guard, what they saved
 * (a skill or an evidence, favourite or read later), the registration switch, and a public key
 * per evidence so the public site can name one without its id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('end_user', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191);
            $table->string('display_name', 50);
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
        DB::statement('CREATE UNIQUE INDEX end_user_email_lower_unique ON end_user (lower(email))');

        Schema::create('end_user_saved', function (Blueprint $table) {
            $table->id();
            $table->foreignId('end_user_id')->constrained('end_user')->cascadeOnDelete();
            $table->string('kind', 20)->comment('favorite | read_later');
            $table->foreignId('skill_id')->nullable()->constrained('skill')->cascadeOnDelete();
            $table->foreignId('evidence_id')->nullable()->constrained('evidence')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
        DB::statement('ALTER TABLE end_user_saved ADD CONSTRAINT end_user_saved_one_target CHECK ((skill_id IS NULL) <> (evidence_id IS NULL))');
        DB::statement('CREATE UNIQUE INDEX end_user_saved_skill_unique ON end_user_saved (end_user_id, kind, skill_id) WHERE skill_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX end_user_saved_evidence_unique ON end_user_saved (end_user_id, kind, evidence_id) WHERE evidence_id IS NOT NULL');

        Schema::create('app_setting', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->jsonb('value');
            $table->foreignId('updated_by')->nullable()->constrained('admin_mst')->nullOnDelete();
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::table('evidence', function (Blueprint $table) {
            $table->uuid('public_key')->default(DB::raw('gen_random_uuid()'))->unique();
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropColumn('public_key');
        });
        Schema::dropIfExists('app_setting');
        Schema::dropIfExists('end_user_saved');
        Schema::dropIfExists('end_user');
    }
};
