<?php

declare(strict_types=1);

namespace App\Http\Resources\Management;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\Management\SocialMgmt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SocialMgmt
 */
class SocialMgmtResource extends JsonResource
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
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'link' => (string) $this->link,
            'image' => (string) $this->image,
            'status' => (int) $this->status?->value,
            'is_display' => (bool) $this->is_display,
            'rank_order' => (int) $this->rank_order,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
