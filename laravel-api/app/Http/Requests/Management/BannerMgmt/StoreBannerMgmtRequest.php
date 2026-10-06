<?php

namespace App\Http\Requests\Management\BannerMgmt;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use App\Rules\IsImageMedia;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreBannerMgmtRequest extends FormRequest
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
            'description' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:255'],
            'position' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'status' => ['required', new Enum(StatusEnum::class)],
            'is_delete' => ['required', new Enum(IsDelete::class)],
            'media_id' => ['nullable', 'integer', 'exists:media_mgmt,id', new IsImageMedia],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('messages.title'),
            'slug' => __('messages.slug'),
            'description' => __('messages.description'),
            'image' => __('messages.image'),
            'position' => __('messages.position'),
            'status' => __('messages.status'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
