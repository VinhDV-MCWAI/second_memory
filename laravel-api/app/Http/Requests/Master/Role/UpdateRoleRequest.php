<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\Role;

use App\Constants\CommonVal;
use App\Constants\PermissionConst;
use App\Rules\UniqueIgnoringCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.PermissionConst::ROLE_NAME_MAX, new UniqueIgnoringCase('roles', 'name', $this->route('id'))],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists(Permission::class, 'name')->where('guard_name', CommonVal::ADMIN_GUARD)],
        ];
    }

    public function attributes(): array
    {
        return ['name' => __('messages.name'), 'permissions' => __('messages.permissions'), 'permissions.*' => __('messages.permissions')];
    }
}
