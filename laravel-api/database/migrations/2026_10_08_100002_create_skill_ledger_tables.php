<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RFC-002 slice 2: Skill Ledger schema (§4.2). Field limits follow ADR-0008.
 * Search columns and indexes come in their own migration (ADR-0009, P3-08).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('slug', 120)->unique()->comment('Public URL segment');
            $table->string('category', 50);
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->smallInteger('current_level')->comment('App\Enums\SkillLevel; equals the latest skill_level row');
            $table->timestamps();
        });

        Schema::create('skill_level', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('skill_id')->constrained('skill')->cascadeOnDelete();
            $table->smallInteger('level')->comment('App\Enums\SkillLevel');
            $table->string('reason', 500)->nullable()->comment('Private, never on the public API');
            $table->date('changed_on');
            $table->unsignedInteger('admin_mst_id')->nullable()->comment('Who recorded it');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('admin_mst_id')->references('id')->on('admin_mst')->nullOnDelete();
            $table->index(['skill_id', 'changed_on', 'id']);
        });

        Schema::create('tag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50);
        });

        Schema::create('skill_tag', function (Blueprint $table) {
            $table->foreignId('skill_id')->constrained('skill')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tag')->cascadeOnDelete();
            $table->primary(['skill_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('evidence', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 20)->comment('App\Enums\EvidenceType');
            $table->string('title', 200);
            $table->string('url', 2048);
            $table->date('occurred_on')->index();
            $table->string('summary', 1000)->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('source', 20)->default('manual')->comment('App\Enums\EvidenceSource');
            $table->string('external_key', 500)->nullable()->unique()->comment('Vault path of an imported note');
            $table->timestamp('unpublished_at')->nullable()->comment('Imported note no longer published');
            $table->timestamps();
        });

        Schema::create('evidence_skill', function (Blueprint $table) {
            $table->foreignId('evidence_id')->constrained('evidence')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skill')->cascadeOnDelete();
            $table->primary(['evidence_id', 'skill_id']);
            $table->index('skill_id');
        });

        Schema::create('evidence_tag', function (Blueprint $table) {
            $table->foreignId('evidence_id')->constrained('evidence')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tag')->cascadeOnDelete();
            $table->primary(['evidence_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('learning_goal', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('skill_id')->constrained('skill')->cascadeOnDelete();
            $table->smallInteger('target_level')->comment('App\Enums\SkillLevel');
            $table->date('target_date')->nullable();
            $table->string('status', 20)->default('open')->comment('App\Enums\GoalStatus');
            $table->date('achieved_on')->nullable();
            $table->string('note', 1000)->nullable();
            $table->timestamps();

            $table->index(['skill_id', 'status']);
        });

        // Names are unique ignoring case (REQ-002 US-1); levels stay on the 1-4 scale even for raw SQL writes
        DB::statement('CREATE UNIQUE INDEX skill_name_lower_unique ON skill (lower(name))');
        DB::statement('CREATE UNIQUE INDEX tag_name_lower_unique ON tag (lower(name))');
        DB::statement('ALTER TABLE skill ADD CONSTRAINT skill_current_level_check CHECK (current_level BETWEEN 1 AND 4)');
        DB::statement('ALTER TABLE skill_level ADD CONSTRAINT skill_level_level_check CHECK (level BETWEEN 1 AND 4)');
        DB::statement('ALTER TABLE learning_goal ADD CONSTRAINT learning_goal_target_level_check CHECK (target_level BETWEEN 1 AND 4)');
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_goal');
        Schema::dropIfExists('evidence_tag');
        Schema::dropIfExists('evidence_skill');
        Schema::dropIfExists('evidence');
        Schema::dropIfExists('skill_tag');
        Schema::dropIfExists('tag');
        Schema::dropIfExists('skill_level');
        Schema::dropIfExists('skill');
    }
};
