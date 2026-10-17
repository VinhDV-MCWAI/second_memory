<?php

declare(strict_types=1);

namespace App\Http\Resources\Media;

use App\Constants\MediaConst;
use App\Models\Media\MediaMgmt;
use App\Services\Media\MediaInfoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A deleted file in the trash. A deleted row is never changed again, so `updated_at` / `updated_by`
 * are when and by whom it was deleted.
 *
 * @mixin MediaMgmt
 */
class TrashItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->original_name,
            /** Where it was before it was deleted */
            'path' => (string) $this->virtual_path,
            /** @var int|null */
            'size' => $this->size,
            'mime_type' => $this->mime_type,
            /** Presigned JPEG, at most 320 px wide (MEDIA-12); null when there is none */
            'thumbnail_url' => MediaInfoService::thumbnailUrl($this->resource),
            'deleted_at' => $this->updated_at?->toIso8601String(),
            'deleted_by' => $this->updater?->user_name,
            /**
             * When MinIO drops it for good
             *
             * @var string|null
             */
            'expires_at' => $this->updated_at?->copy()->addDays(MediaConst::TRASH_DAYS)->toIso8601String(),
        ];
    }
}
