<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiRoleMstResource extends JsonResource
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
            'api_mst_id' => (int) $this->api_mst_id,
            'role_mst_id' => (int) $this->role_mst_id,
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
