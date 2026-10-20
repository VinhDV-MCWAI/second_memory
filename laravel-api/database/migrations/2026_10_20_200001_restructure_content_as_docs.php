<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0022: categories form two levels (group → category) and carry attachments (links or files of
 * the file manager); an article belongs to any number of categories and may sit under a parent
 * article (a book of chapters). Article slugs become unique site-wide so `[[slug]]` names one
 * article. Existing articles keep their category through the new link table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_category', function (Blueprint $table) {
            // NO ACTION, checked at the end of the statement: a group and its categories can go together
            $table->foreignId('parent_id')->nullable()->constrained('content_category')->noActionOnDelete();
            $table->text('description')->nullable()->change();
        });

        Schema::create('article_category', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('article')->cascadeOnDelete();
            $table->foreignId('content_category_id')->constrained('content_category')->cascadeOnDelete();
            $table->primary(['article_id', 'content_category_id']);
            $table->index('content_category_id');
        });
        DB::statement('INSERT INTO article_category (article_id, content_category_id) SELECT id, content_category_id FROM article');

        // A slug used in two categories keeps its first article; the others get their id appended
        DB::statement("UPDATE article a SET slug = a.slug || '-' || a.id WHERE EXISTS (SELECT 1 FROM article b WHERE b.slug = a.slug AND b.id < a.id)");
        Schema::table('article', function (Blueprint $table) {
            $table->dropUnique(['content_category_id', 'slug']);
            $table->dropConstrainedForeignId('content_category_id');
            $table->unique('slug');
            $table->foreignId('parent_id')->nullable()->constrained('article')->noActionOnDelete();
            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create('category_attachment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_category_id')->constrained('content_category')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('url', 2048)->nullable();
            // Deleting the file for good removes the attachment; a trashed file keeps it
            $table->foreignId('media_id')->nullable()->constrained('media_mgmt')->cascadeOnDelete();
            $table->string('note', 500)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            // Key of public/files/{key}; set by the database so a re-saved attachment keeps it
            $table->uuid('public_id')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->timestamps();
            $table->index(['content_category_id', 'position']);
            $table->index('media_id');
        });
        DB::statement('ALTER TABLE category_attachment ADD CONSTRAINT category_attachment_link_or_file CHECK (num_nonnulls(url, media_id) >= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('category_attachment');

        Schema::table('article', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'sort_order']);
            $table->dropConstrainedForeignId('parent_id');
            $table->dropUnique(['slug']);
            $table->foreignId('content_category_id')->nullable()->constrained('content_category')->restrictOnDelete();
        });
        // Back to one category per article: the first linked one; articles without any are removed
        DB::statement('UPDATE article SET content_category_id = (SELECT min(content_category_id) FROM article_category WHERE article_id = article.id)');
        DB::table('article')->whereNull('content_category_id')->delete();
        Schema::table('article', function (Blueprint $table) {
            $table->foreignId('content_category_id')->nullable(false)->change();
            $table->unique(['content_category_id', 'slug']);
        });
        Schema::dropIfExists('article_category');

        // Groups lose their categories' parent link; descriptions are cut to the old length
        Schema::table('content_category', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
        DB::statement('UPDATE content_category SET description = left(description, 500)');
        Schema::table('content_category', function (Blueprint $table) {
            $table->string('description', 500)->nullable()->change();
        });
    }
};
