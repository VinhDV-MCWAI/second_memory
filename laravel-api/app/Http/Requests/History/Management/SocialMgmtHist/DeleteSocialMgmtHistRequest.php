<?php

namespace App\Http\Requests\History\Management\SocialMgmtHist;

use App\Constants\CommonVal;
use App\Models\History\Management\SocialMgmtHist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteSocialMgmtHistRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_BIG_INTEGER, Rule::exists(SocialMgmtHist::class, 'id')],
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
