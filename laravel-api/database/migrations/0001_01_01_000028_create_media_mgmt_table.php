<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_mgmt', function (Blueprint $table) {
            $table->id();
            $table->integer('workspace_id')->nullable()->comment('Workspace ID (null for now, will configure later)');

            // Type
            $table->boolean('is_file')->default(true)->comment('true: file, false: folder');

            // Paths
            $table->string('virtual_path', 1000)->comment('Virtual path for UI tree: /Documents/file.pdf');
            $table->string('storage_path', 1000)->nullable()->comment('Real MinIO path: workspace-1/2025/01/uuid.pdf');

            // File info
            $table->string('original_name')->comment('Display name');
            $table->string('extension', 50)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('size')->nullable()->comment('File size in bytes');

            // MinIO
            $table->string('minio_bucket', 100)->nullable();
            $table->string('minio_object_key', 500)->nullable();
            $table->string('minio_etag', 100)->nullable();

            // URL
            $table->text('url')->nullable();

            // Metadata
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable()->comment('Video duration in seconds');
            $table->jsonb('metadata')->nullable();

            // Soft delete
            $table->boolean('is_delete')->default(false)->comment('is deleted');

            // Audit
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['workspace_id', 'is_delete']);
            $table->index('virtual_path');
            $table->index('is_file');
            $table->index('mime_type');
            $table->unique(['workspace_id', 'virtual_path', 'is_delete'], 'unique_virtual_path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_mgmt');
    }
};
