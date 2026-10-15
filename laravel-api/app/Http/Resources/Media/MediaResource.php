<?php

declare(strict_types=1);

namespace App\Http\Resources\Media;

use App\Constants\MediaConst;
use App\Enums\PreviewKind;
use App\Enums\UploadStatus;
use App\Models\Ledger\Evidence;
use App\Models\Media\MediaMgmt;
use App\Services\Media\MediaInfoService;
use App\Services\Media\ObjectStorage;
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
            /** Image width and height in pixels (MEDIA-10); null for other files or an unreadable image */
            'width' => $this->width,
            'height' => $this->height,
            /** Video or audio length in seconds (MEDIA-11) */
            'duration' => $this->duration,
            /** Presigned JPEG of an image or a video frame, at most 320 px wide (MEDIA-11); null when there is none */
            'thumbnail_url' => $this->thumbnailUrl(),
            /** 0 uploading, 1 processing, 2 completed, 3 failed */
            'upload_status' => $this->upload_status->value,
            /** How the file can be shown without downloading it (`download-url/{id}?inline=1`); null: download only */
            'preview' => $this->is_file && $this->upload_status === UploadStatus::COMPLETED ? PreviewKind::forFile($this->extension, $this->size)?->value : null,
            /**
             * Links to evidence: of the file, or of the files inside the folder (MEDIA-09; Files listing only)
             *
             * @var int
             */
            'attached_count' => $this->whenHas('attached_count', fn () => (int) $this->getAttribute('attached_count')),
            /** Evidence this file is attached to (Files listing only) */
            'used_by' => $this->whenLoaded('evidence', fn () => $this->evidence
                ->map(fn (Evidence $evidence): array => ['id' => (int) $evidence->id, 'title' => (string) $evidence->title])
                ->all()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /** Signed here, like every MinIO link: a signature is computed locally, no request to MinIO. */
    private function thumbnailUrl(): ?string
    {
        $key = MediaInfoService::thumbnailKey($this->resource);

        return $key === null ? null : app(ObjectStorage::class)
            ->presignedGetUrl($key, pathinfo((string) $this->original_name, PATHINFO_FILENAME).'.jpg', MediaConst::PREVIEW_URL_TTL_SECONDS, MediaConst::THUMBNAIL_CONTENT_TYPE);
    }
}
