<?php

namespace App\Http\Resources\History\Master;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureMstHistResource extends JsonResource
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
            'feature_mst_id' => (int) $this->feature_mst_id,
            'name' => (string) $this->name,
            'group_name' => (string) $this->group_name,
            'description' => (string) $this->description,
            'status' => (string) $this->status,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->created_at)),
        ];
    }
}
