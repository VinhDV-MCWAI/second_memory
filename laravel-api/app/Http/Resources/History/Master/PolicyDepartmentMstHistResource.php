<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Master;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\History\Master\PolicyDepartmentMstHist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PolicyDepartmentMstHist
 */
class PolicyDepartmentMstHistResource extends JsonResource
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
            'policy_department_mst_id' => (int) $this->policy_department_mst_id,
            'table_name' => (int) $this->table_name,
            'row_id' => (int) $this->row_id,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
