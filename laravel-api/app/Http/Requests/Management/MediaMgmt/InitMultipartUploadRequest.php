<?php

namespace App\Http\Requests\Management\MediaMgmt;

use Illuminate\Foundation\Http\FormRequest;

class InitMultipartUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'extension' => 'required|string',
            'size' => 'required|integer|min:1',
            'mime_type' => 'nullable|string',
            'original_name' => 'nullable|string',
        ];
    }
}
