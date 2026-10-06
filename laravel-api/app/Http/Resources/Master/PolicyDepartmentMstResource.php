<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyDepartmentMstResource extends JsonResource
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
            'id' => (int) $this->id,
            'table_name' => (string) $this->table_name,
            'row_id' => (int) $this->row_id,
            'is_delete' => (bool) $this->is_delete,
            'departments' => DepartmentMstResource::collection($this->whenLoaded('departments')),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
