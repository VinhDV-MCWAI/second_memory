<?php

namespace App\Http\Requests\Master\TokenMst;

use App\Constants\CommonVal;
use App\Models\Master\TokenMst;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTokenMstRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(TokenMst::class, 'id')],
            'account_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'device_name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:255'],
            'ip_address' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:255'],
            'expired_at' => ['date_format:'.CommonVal::DATE_FORMAT, 'after_or_equal:'.CommonVal::MIN_DATE, 'before_or_equal:'.CommonVal::MAX_DATE],
        ];
    }

    public function attributes(): array
    {
        return [
            'account_id' => __('messages.account_id'),
            'device_name' => __('messages.device_name'),
            'ip_address' => __('messages.ip_address'),
            'expired_at' => __('messages.expired_at'),
        ];
    }
}
