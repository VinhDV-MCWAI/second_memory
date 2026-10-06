<?php

namespace App\Http\Requests\History\Master\FeatureMstHist;

use App\Constants\CommonVal;
use App\Enums\StatusEnum;
use App\Models\History\Master\FeatureMstHist;
use App\Models\Master\FeatureMst;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeatureMstHistRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(FeatureMstHist::class, 'id')],
            'feature_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(FeatureMst::class, 'id')],
            'name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'group_name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'description' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'status' => [Rule::enum(StatusEnum::class)],
            'action' => ['required'],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
        ];
    }

    public function attributes(): array
    {
        return [
            'feature_mst_id' => __('messages.feature_mst_id'),
            'name' => __('messages.name'),
            'group_name' => __('messages.group_name'),
            'description' => __('messages.description'),
            'status' => __('messages.status'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
