<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Tag;

use App\Models\Ledger\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', Rule::exists(Tag::class, 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => __('messages.ids'), 'ids.*' => __('messages.ids')];
    }
}
