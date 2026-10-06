<?php

declare(strict_types=1);

namespace App\Http\Requests\Management\EntryDescriptionMgmt;

use App\Constants\CommonVal;
use App\Models\Management\EntryDescriptionMgmt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteEntryDescriptionMgmtRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_BIG_INTEGER, Rule::exists(EntryDescriptionMgmt::class, 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => __('messages.ids'),
            'ids.*' => __('messages.ids'),
        ];
    }
}
