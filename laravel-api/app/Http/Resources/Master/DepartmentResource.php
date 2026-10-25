<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Models\Master\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Department
 */
class DepartmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'admin_count' => (int) $this->admins_count,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
