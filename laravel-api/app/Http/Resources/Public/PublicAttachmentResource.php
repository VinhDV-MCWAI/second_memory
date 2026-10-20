<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\CategoryAttachment;
use App\Services\Media\MediaInfoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Evidence of a public category (ADR-0022). A file shows only while it is available in the file
 * manager; `file.key` opens it through public/files/{key}. No ids, no folder paths.
 *
 * @mixin CategoryAttachment
 */
class PublicAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $media = $this->media;

        return [
            'title' => (string) $this->title,
            'url' => $this->url,
            'note' => $this->note,
            /** @var array{key: string, name: string, size: int|null, mime_type: string|null, thumbnail_url: string|null}|null */
            'file' => $media === null || ! $media->isAvailable() ? null : [
                'key' => (string) $this->public_id,
                'name' => (string) $media->original_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'thumbnail_url' => MediaInfoService::thumbnailUrl($media),
            ],
        ];
    }
}
