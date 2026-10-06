<?php

namespace App\Http\Resources\History\Management;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryDescriptionMgmtHistResource extends JsonResource
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
            'entry_description_mgmt_id' => (int) $this->entry_description_mgmt_id,
            'title' => (string) $this->title,
            'summary' => (string) $this->summary,
            'article' => (string) $this->article,
            'status' => (string) $this->status,
            'is_display' => (bool) $this->is_display,
            'rank_order' => (string) $this->rank_order,
            'entry_id' => (int) $this->entry_id,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->created_at)),
        ];
    }
}
