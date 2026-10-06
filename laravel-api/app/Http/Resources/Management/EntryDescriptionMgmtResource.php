<?php

namespace App\Http\Resources\Management;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryDescriptionMgmtResource extends JsonResource
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
            'summary' => (string) $this->summary,
            'article' => json_encode($this->article, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), // Convert array to JSON string for frontend
            'status' => (string) $this->status?->value,
            'is_display' => (bool) $this->is_display,
            'rank_order' => (string) $this->rank_order,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->updated_at)),
        ];
    }
}
