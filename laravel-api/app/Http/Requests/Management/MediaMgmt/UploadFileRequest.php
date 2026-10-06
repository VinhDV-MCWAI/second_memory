<?php

declare(strict_types=1);

namespace App\Http\Requests\Management\MediaMgmt;

use App\Constants\MediaConst;
use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allExtensions = array_merge(
            MediaConst::ALLOWED_IMAGE_EXTENSIONS,
            MediaConst::ALLOWED_VIDEO_EXTENSIONS,
            MediaConst::ALLOWED_DOCUMENT_EXTENSIONS,
            MediaConst::ALLOWED_ARCHIVE_EXTENSIONS
        );

        return [
            'file' => [
                'required',
                'file',
                'max:'.(MediaConst::MAX_FILE_SIZE / 1024), // Convert to KB
                'mimes:'.implode(',', $allExtensions),
            ],
            'parent_path' => 'nullable|string',
            'workspace_id' => 'nullable|integer',
        ];
    }
}
