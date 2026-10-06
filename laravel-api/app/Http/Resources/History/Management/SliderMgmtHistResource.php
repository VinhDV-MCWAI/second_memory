<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Management;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\History\Management\SliderMgmtHist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SliderMgmtHist
 */
class SliderMgmtHistResource extends JsonResource
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
            'slider_mgmt_id' => (int) $this->slider_mgmt_id,
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'link' => (string) $this->link,
            'image' => (string) $this->image,
            'status' => (string) $this->status,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
