<?php

namespace App\Http\Resources\History\Master;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentMstHistResource extends JsonResource
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
            'department_mst_id' => (int) $this->department_mst_id,
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'status' => (string) $this->status,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->created_at)),
        ];
    }
}
