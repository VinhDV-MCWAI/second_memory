<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentManagementMstResource extends JsonResource
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
            'department_mst_id' => (int) $this->department_mst_id,
            'policy_department_mst_id' => (int) $this->policy_department_mst_id,
            'department' => new DepartmentMstResource($this->whenLoaded('department')),
            'policy' => new PolicyDepartmentMstResource($this->whenLoaded('policy')),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
