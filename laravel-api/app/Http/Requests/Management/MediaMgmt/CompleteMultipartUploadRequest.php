<?php

declare(strict_types=1);

namespace App\Http\Requests\Management\MediaMgmt;

use Illuminate\Foundation\Http\FormRequest;

class CompleteMultipartUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string',
            'upload_id' => 'required|string',
            'parts' => 'required|array',
            'parts.*.part_number' => 'required|integer|min:1',
            'parts.*.etag' => 'required|string',
            'original_name' => 'required|string',
            'extension' => 'required|string',
            'size' => 'required|integer|min:1',
            'parent_path' => 'nullable|string',
            'workspace_id' => 'nullable|integer',
            'mime_type' => 'nullable|string',
        ];
    }
}
