<?php

declare(strict_types=1);

namespace App\Http\Requests\History\Management\SliderMgmtHist;

use App\Constants\CommonVal;
use App\Enums\StatusEnum;
use App\Models\Management\SliderMgmt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSliderMgmtHistRequest extends FormRequest
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
            'slider_mgmt_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(SliderMgmt::class, 'id')],
            'title' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'slug' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'link' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'image' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'status' => [Rule::enum(StatusEnum::class)],
            'action' => ['required'],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
        ];
    }

    public function attributes(): array
    {
        return [
            'slider_mgmt_id' => __('messages.slider_mgmt_id'),
            'title' => __('messages.title'),
            'slug' => __('messages.slug'),
            'link' => __('messages.link'),
            'image' => __('messages.image'),
            'status' => __('messages.status'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
