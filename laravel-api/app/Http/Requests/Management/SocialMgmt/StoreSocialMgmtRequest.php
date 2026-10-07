<?php

declare(strict_types=1);

namespace App\Http\Requests\Management\SocialMgmt;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSocialMgmtRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'slug' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'link' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:255'],
            'image' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'is_display' => ['required', Rule::enum(IsActive::class)],
            'rank_order' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'is_delete' => ['required', Rule::enum(IsDelete::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('messages.name'),
            'slug' => __('messages.slug'),
            'link' => __('messages.link'),
            'image' => __('messages.image'),
            'status' => __('messages.status'),
            'is_display' => __('messages.is_display'),
            'rank_order' => __('messages.rank_order'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
