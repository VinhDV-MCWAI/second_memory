<?php

namespace App\Http\Requests\Management\MediaMgmt;

use Illuminate\Foundation\Http\FormRequest;

class GetMultipartUrlRequest extends FormRequest
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
            'part_number' => 'required|integer|min:1',
            'size' => 'required|integer|min:0',
        ];
    }
}
