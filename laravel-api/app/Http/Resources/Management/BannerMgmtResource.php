<?php

namespace App\Http\Resources\Management;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerMgmtResource extends JsonResource
{
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
            'status' => (string) $this->status,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->updated_at)),
        ];
    }
}
