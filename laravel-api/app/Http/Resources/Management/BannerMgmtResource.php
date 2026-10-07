<?php

declare(strict_types=1);

namespace App\Http\Resources\Management;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\Management\BannerMgmt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BannerMgmt
 */
class BannerMgmtResource extends JsonResource
{
    use FormatsDates;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'description' => (string) $this->description,
            'link' => (string) $this->link,
            'image' => (string) ($this->media?->url ?? $this->image), // Fallback to image column if media not found (backward compatibility)
            'media_id' => (int) $this->media_id,
            'position' => (string) $this->position,
            'status' => (int) $this->status?->value,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
