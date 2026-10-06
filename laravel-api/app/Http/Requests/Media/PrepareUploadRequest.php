<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class PrepareUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Auth handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'extension' => 'required|string|max:10',
            'original_name' => 'required|string|max:255',
            'size' => 'sometimes|integer|min:0',
            'mime_type' => 'sometimes|string|max:255',
        ];
    }
}
