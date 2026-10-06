<?php

namespace App\Http\Resources\History\Management;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryMgmtHistResource extends JsonResource
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
            'category_mgmt_id' => (int) $this->category_mgmt_id,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'description' => (string) $this->description,
            'status' => (string) $this->status,
            'is_display' => (bool) $this->is_display,
            'rank_order' => (string) $this->rank_order,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->created_at)),
        ];
    }
}
