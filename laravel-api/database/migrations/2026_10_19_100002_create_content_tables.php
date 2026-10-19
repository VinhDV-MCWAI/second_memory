<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Categories and articles of the public site (ADR-0021). A category with articles cannot be
 * deleted (restrict); end users may save an article like a skill or an evidence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_category', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
        DB::statement('CREATE UNIQUE INDEX content_category_name_lower_unique ON content_category (lower(name))');

        Schema::create('article', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_category_id')->constrained('content_category')->restrictOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220);
            $table->string('summary', 500)->nullable();
            $table->text('body');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            // Names the article for end users' saved lists without its id (like evidence.public_key)
            $table->uuid('public_key')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->timestamps();
            $table->unique(['content_category_id', 'slug']);
        });

        Schema::table('end_user_saved', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()->constrained('article')->cascadeOnDelete();
        });
        DB::statement('ALTER TABLE end_user_saved DROP CONSTRAINT end_user_saved_one_target');
        DB::statement('ALTER TABLE end_user_saved ADD CONSTRAINT end_user_saved_one_target CHECK (num_nonnulls(skill_id, evidence_id, article_id) = 1)');
        DB::statement('CREATE UNIQUE INDEX end_user_saved_article_unique ON end_user_saved (end_user_id, kind, article_id) WHERE article_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::table('end_user_saved')->whereNotNull('article_id')->delete();
        DB::statement('ALTER TABLE end_user_saved DROP CONSTRAINT end_user_saved_one_target');
        Schema::table('end_user_saved', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_id');
        });
        DB::statement('ALTER TABLE end_user_saved ADD CONSTRAINT end_user_saved_one_target CHECK ((skill_id IS NULL) <> (evidence_id IS NULL))');

        Schema::dropIfExists('article');
        Schema::dropIfExists('content_category');
    }
};
