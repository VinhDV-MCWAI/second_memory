<?php

namespace App\Http\Resources\Master;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentManagementMstResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'department_mst_id' => (int) $this->department_mst_id,
            'policy_department_mst_id' => (int) $this->policy_department_mst_id,
            'department' => new DepartmentMstResource($this->whenLoaded('department')),
            'policy' => new PolicyDepartmentMstResource($this->whenLoaded('policy')),
            'updated_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->updated_at)),
        ];
    }
}
