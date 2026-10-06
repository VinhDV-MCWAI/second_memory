<?php

namespace App\Http\Resources\Master;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiMstResource extends JsonResource
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
            'type' => (int) $this->type,
            'name' => (string) $this->name,
            'path' => (string) $this->path,
            'is_active' => (bool) $this->is_active,
            'feature_mst_id' => (int) $this->feature_mst_id,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->updated_at)),
        ];
    }
}
