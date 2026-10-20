<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Files of the file manager embedded in an article with `![[file:{id}]]` (ADR-0022), kept in step
 * with the body on every save. The public page names a file by `public_id`, never by its id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_media', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('article')->cascadeOnDelete();
            // Deleting the file for good removes the link; a trashed file keeps it
            $table->foreignId('media_id')->constrained('media_mgmt')->cascadeOnDelete();
            $table->uuid('public_id')->default(DB::raw('gen_random_uuid()'))->unique()->comment('Key of public/files/{key}');
            $table->primary(['article_id', 'media_id']);
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_media');
    }
};
