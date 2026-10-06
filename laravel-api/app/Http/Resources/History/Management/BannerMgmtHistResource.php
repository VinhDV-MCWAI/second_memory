<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Management;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\History\Management\BannerMgmtHist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BannerMgmtHist
 */
class BannerMgmtHistResource extends JsonResource
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
            'banner_mgmt_id' => (int) $this->banner_mgmt_id,
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'description' => (string) $this->description,
            'link' => (string) $this->link,
            'image' => (string) $this->image,
            'position' => (string) $this->position,
            'status' => (string) $this->status,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
