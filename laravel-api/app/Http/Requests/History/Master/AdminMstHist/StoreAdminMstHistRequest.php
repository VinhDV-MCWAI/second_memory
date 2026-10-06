<?php

declare(strict_types=1);

namespace App\Http\Requests\History\Master\AdminMstHist;

use App\Constants\CommonVal;
use App\Enums\Gender;
use App\Enums\IsActive;
use App\Enums\StatusEnum;
use App\Models\History\Master\AdminMstHist;
use App\Models\Master\AdminMst;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminMstHistRequest extends FormRequest
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
            'admin_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(AdminMst::class, 'id')],
            'email' => ['email:rfc,dns', 'min:'.CommonVal::MIN_VARCHAR, 'max:'.CommonVal::MAX_EMAIL, Rule::unique(AdminMstHist::class, 'email')],
            'user_name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'password' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'first_name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:20'],
            'last_name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:20'],
            'address' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'phone_number' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:'.CommonVal::MAX_PHONE_NUMBER],
            'birth' => ['date_format:'.CommonVal::DATE_FORMAT, 'after_or_equal:'.CommonVal::MIN_DATE, 'before_or_equal:'.CommonVal::MAX_DATE],
            'gender' => [Rule::enum(Gender::class)],
            'status' => [Rule::enum(StatusEnum::class)],
            'is_active' => [Rule::enum(IsActive::class)],
            'avatar' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:30'],
            'action' => ['required'],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
        ];
    }

    public function attributes(): array
    {
        return [
            'admin_mst_id' => __('messages.admin_mst_id'),
            'email' => __('messages.email'),
            'user_name' => __('messages.user_name'),
            'password' => __('messages.password'),
            'first_name' => __('messages.first_name'),
            'last_name' => __('messages.last_name'),
            'address' => __('messages.address'),
            'phone_number' => __('messages.phone_number'),
            'birth' => __('messages.birth'),
            'gender' => __('messages.gender'),
            'status' => __('messages.status'),
            'is_active' => __('messages.is_active'),
            'avatar' => __('messages.avatar'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
