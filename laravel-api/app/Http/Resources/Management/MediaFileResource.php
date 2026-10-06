<?php

namespace App\Http\Resources\Management;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'is_file' => $this->is_file,
            'virtual_path' => $this->virtual_path,
            'storage_path' => $this->storage_path,
            'original_name' => $this->original_name,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_size' => $this->size ? $this->formatFileSize($this->size) : null,
            'folder_path' => $this->folder_path,  // Uses accessor from model
            'minio_bucket' => $this->minio_bucket,
            'minio_object_key' => $this->minio_object_key,
            'minio_etag' => $this->minio_etag,
            'url' => $this->url,
            'view_url' => $this->url,  // Use the stored URL from MinIO
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'upload_status' => $this->upload_status,
        ];
    }

    private function formatFileSize(?int $bytes): ?string
    {
        if ($bytes === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
