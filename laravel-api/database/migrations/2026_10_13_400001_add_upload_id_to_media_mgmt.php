<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0014: a large file goes up in parts (S3 multipart upload); the MinIO upload id is kept until the
 * parts are joined, so the upload can be finished, cancelled or cleaned up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_mgmt', function (Blueprint $table) {
            $table->string('upload_id', 255)->nullable()->after('minio_etag')->comment('MinIO multipart upload id while the parts are uploading');
        });
    }

    public function down(): void
    {
        Schema::table('media_mgmt', function (Blueprint $table) {
            $table->dropColumn('upload_id');
        });
    }
};
