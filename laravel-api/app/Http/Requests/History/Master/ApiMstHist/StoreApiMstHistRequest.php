<?php

namespace App\Http\Requests\History\Master\ApiMstHist;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreApiMstHistRequest extends FormRequest
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
            'api_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(ApiMst::class, 'id')],
            'type' => [],
            'name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'path' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'is_active' => [new Enum(IsActive::class)],
            'feature_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(FeatureMst::class, 'id')],
            'action' => ['required'],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
        ];
    }

    public function attributes(): array
    {
        return [
            'api_mst_id' => __('messages.api_mst_id'),
            'type' => __('messages.type'),
            'name' => __('messages.name'),
            'path' => __('messages.path'),
            'is_active' => __('messages.is_active'),
            'feature_mst_id' => __('messages.feature_mst_id'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
