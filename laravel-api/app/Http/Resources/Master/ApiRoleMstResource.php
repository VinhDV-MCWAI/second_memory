<?php

namespace App\Http\Resources\Master;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiRoleMstResource extends JsonResource
{
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
            'updated_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->updated_at)),
        ];
    }
}
