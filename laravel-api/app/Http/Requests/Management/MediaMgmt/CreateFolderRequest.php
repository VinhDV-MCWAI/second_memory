<?php

declare(strict_types=1);

namespace App\Http\Requests\Management\MediaMgmt;

use Illuminate\Foundation\Http\FormRequest;

class CreateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9_\-\s]+$/',
            'parent_path' => 'nullable|string',
            'workspace_id' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Folder name can only contain letters, numbers, spaces, hyphens and underscores.',
        ];
    }
}
