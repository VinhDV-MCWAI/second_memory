<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Enums\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            /** Built-in roles (`owner`, `viewer`) cannot be renamed or deleted. */
            'is_built_in' => AdminRole::tryFrom((string) $this->name) !== null,
            'permissions' => $this->permissions->map(fn (Permission $permission): string => (string) $permission->name)->sort()->values()->all(),
            'admin_count' => (int) $this->resource->getAttribute('admin_count'),
        ];
    }
}
