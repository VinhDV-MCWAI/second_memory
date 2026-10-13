<?php

declare(strict_types=1);

namespace App\Http\Resources\Media;

use App\Models\Media\MediaMgmt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MediaMgmt
 */
class MediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'is_file' => (bool) $this->is_file,
            'name' => (string) $this->original_name,
            'path' => (string) $this->virtual_path,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            /** @var int|null */
            'size' => $this->size,
            /** 0 uploading, 1 processing, 2 completed, 3 failed */
            'upload_status' => $this->upload_status->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
