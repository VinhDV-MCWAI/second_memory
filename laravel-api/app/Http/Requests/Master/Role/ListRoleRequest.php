<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\Role;

use App\Constants\PermissionConst;
use App\Http\Requests\ListRequest;

class ListRoleRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:'.PermissionConst::ROLE_NAME_MAX],
        ];
    }
}
