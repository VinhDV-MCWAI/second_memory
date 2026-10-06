<?php

declare(strict_types=1);

namespace App\Http\Requests\Management\SliderMgmt;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSliderMgmtRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'slug' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'link' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'image' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'is_delete' => ['required', Rule::enum(IsDelete::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('messages.title'),
            'slug' => __('messages.slug'),
            'link' => __('messages.link'),
            'image' => __('messages.image'),
            'status' => __('messages.status'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
