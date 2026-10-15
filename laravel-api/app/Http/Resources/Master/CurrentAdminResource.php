<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;

/**
 * The signed-in admin plus the permission names it holds, so the admin UI can hide what it may not use.
 */
class CurrentAdminResource extends AdminMstResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'permissions' => $this->permissionNames(),
        ];
    }
}
