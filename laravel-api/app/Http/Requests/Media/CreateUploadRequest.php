<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Constants\MediaConst;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One-PUT upload of a small file (ADR-0014); bigger files use multipart upload.
 */
class CreateUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folder' => MediaRules::folder(),
            'name' => ['required', ...MediaRules::name()],
            'size' => ['required', 'integer', 'min:0', 'max:'.MediaConst::SINGLE_UPLOAD_MAX_BYTES],
            'mime_type' => ['required', 'string', 'max:'.MediaConst::MIME_TYPE_MAX, 'regex:/^[\w.+-]+\/[\w.+-]+$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'folder' => __('messages.folder'),
            'name' => __('messages.name'),
            'size' => __('messages.size'),
            'mime_type' => __('messages.mime_type'),
        ];
    }
}
