<?php

namespace App\Http\Resources\Master;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyDepartmentMstResource extends JsonResource
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
            'table_name' => (string) $this->table_name,
            'row_id' => (int) $this->row_id,
            'is_delete' => (bool) $this->is_delete,
            'departments' => DepartmentMstResource::collection($this->whenLoaded('departments')),
            'updated_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->updated_at)),
        ];
    }
}
