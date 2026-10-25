<?php

declare(strict_types=1);

use App\Enums\SavedKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0024: categories get their own view count and can be an end user's favourite; a read-later
 * article keeps the heading the reader reached (its anchor) and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_category', function (Blueprint $table) {
            $table->unsignedBigInteger('view_count')->default(0);
        });

        DB::statement('ALTER TABLE end_user_saved ALTER COLUMN article_id DROP NOT NULL');
        Schema::table('end_user_saved', function (Blueprint $table) {
            $table->foreignId('content_category_id')->nullable()->constrained('content_category')->cascadeOnDelete();
            $table->string('reading_anchor', 255)->nullable();
            $table->timestamp('read_at')->nullable();
        });
        DB::statement('ALTER TABLE end_user_saved ADD CONSTRAINT end_user_saved_one_target CHECK (num_nonnulls(article_id, content_category_id) = 1)');
        // Read later is for articles only
        DB::statement("ALTER TABLE end_user_saved ADD CONSTRAINT end_user_saved_category_favorite CHECK (content_category_id IS NULL OR kind = '".SavedKind::FAVORITE->value."')");
        DB::statement('CREATE UNIQUE INDEX end_user_saved_category_unique ON end_user_saved (end_user_id, kind, content_category_id) WHERE content_category_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::table('end_user_saved')->whereNull('article_id')->delete();
        DB::statement('DROP INDEX end_user_saved_category_unique');
        DB::statement('ALTER TABLE end_user_saved DROP CONSTRAINT end_user_saved_category_favorite');
        DB::statement('ALTER TABLE end_user_saved DROP CONSTRAINT end_user_saved_one_target');
        Schema::table('end_user_saved', function (Blueprint $table) {
            $table->dropConstrainedForeignId('content_category_id');
            $table->dropColumn(['reading_anchor', 'read_at']);
        });
        DB::statement('ALTER TABLE end_user_saved ALTER COLUMN article_id SET NOT NULL');

        Schema::table('content_category', function (Blueprint $table) {
            $table->dropColumn('view_count');
        });
    }
};
