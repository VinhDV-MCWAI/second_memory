<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Management;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialMgmtHistResource extends JsonResource
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
            'social_mgmt_id' => (int) $this->social_mgmt_id,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'link' => (string) $this->link,
            'image' => (string) $this->image,
            'status' => (int) $this->status,
            'is_display' => (bool) $this->is_display,
            'rank_order' => (int) $this->rank_order,
            'action' => (int) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
