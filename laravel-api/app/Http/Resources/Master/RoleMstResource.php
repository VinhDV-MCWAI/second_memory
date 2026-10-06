<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleMstResource extends JsonResource
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
            'name' => (string) $this->name,
            'permission' => (string) $this->permission,
            'is_active' => (bool) $this->is_active,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
