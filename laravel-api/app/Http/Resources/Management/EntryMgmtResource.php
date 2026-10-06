<?php

namespace App\Http\Resources\Management;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryMgmtResource extends JsonResource
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
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'status' => (string) $this->status,
            'is_display' => (bool) $this->is_display,
            'rank_order' => (string) $this->rank_order,
            'layout_structure' => $this->layout_structure,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->updated_at)),
        ];
    }
}
