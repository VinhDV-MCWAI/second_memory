<?php

namespace App\Http\Requests\History\Management\SocialMgmtHist;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\StatusEnum;
use App\Models\Management\SocialMgmt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSocialMgmtHistRequest extends FormRequest
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
            'social_mgmt_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(SocialMgmt::class, 'id')],
            'name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'slug' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'link' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:255'],
            'image' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'status' => [Rule::enum(StatusEnum::class)],
            'is_display' => [Rule::enum(IsActive::class)],
            'rank_order' => ['integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'action' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
        ];
    }

    public function attributes(): array
    {
        return [
            'social_mgmt_id' => __('messages.social_mgmt_id'),
            'name' => __('messages.name'),
            'slug' => __('messages.slug'),
            'link' => __('messages.link'),
            'image' => __('messages.image'),
            'status' => __('messages.status'),
            'is_display' => __('messages.is_display'),
            'rank_order' => __('messages.rank_order'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
