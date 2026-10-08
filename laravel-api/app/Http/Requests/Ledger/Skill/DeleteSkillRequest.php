<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Skill;

use App\Models\Ledger\Skill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', Rule::exists(Skill::class, 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => __('messages.ids'), 'ids.*' => __('messages.ids')];
    }
}
