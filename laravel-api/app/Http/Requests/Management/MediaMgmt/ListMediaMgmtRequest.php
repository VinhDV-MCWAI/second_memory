<?php

namespace App\Http\Requests\Management\MediaMgmt;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListMediaMgmtRequest extends FormRequest
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
            'parent_path' => ['nullable', 'string', 'max:1000'],
            'folder_path' => ['nullable', 'string', 'max:1000'],
            'is_file' => ['nullable', 'boolean'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'order_by' => ['nullable', 'string', 'in:created_at,original_name,size'],
            'order_direction' => ['nullable', 'string', 'in:asc,desc'],
            'id' => ['nullable', 'integer'],
            // No per_page/page validation - file manager loads all files
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_path' => __('messages.parent_path'),
            'folder_path' => __('messages.folder_path'),
            'is_file' => __('messages.is_file'),
            'mime_type' => __('messages.mime_type'),
            'search' => __('messages.search'),
            'order_by' => __('messages.order_by'),
            'order_direction' => __('messages.order_direction'),
        ];
    }
}
