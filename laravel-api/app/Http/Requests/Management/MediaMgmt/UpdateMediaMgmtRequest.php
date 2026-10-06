<?php

namespace App\Http\Requests\Management\MediaMgmt;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaMgmtRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\s]+$/'],
            'new_parent_path' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('messages.name'),
            'new_parent_path' => __('messages.new_parent_path'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name can only contain letters, numbers, spaces, hyphens and underscores.',
        ];
    }
}
