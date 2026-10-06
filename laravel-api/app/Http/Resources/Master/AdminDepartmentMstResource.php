<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDepartmentMstResource extends JsonResource
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
            'admin_mst_id' => (int) $this->admin_mst_id,
            'department_mst_id' => (int) $this->department_mst_id,
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
