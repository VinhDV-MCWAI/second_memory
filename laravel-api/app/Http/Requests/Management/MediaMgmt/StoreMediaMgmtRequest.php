<?php

namespace App\Http\Requests\Management\MediaMgmt;

use App\Constants\MediaConst;
use App\Enums\IsDelete;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaMgmtRequest extends FormRequest
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
        $allExtensions = array_merge(
            MediaConst::ALLOWED_IMAGE_EXTENSIONS,
            MediaConst::ALLOWED_VIDEO_EXTENSIONS,
            MediaConst::ALLOWED_AUDIO_EXTENSIONS,
            MediaConst::ALLOWED_DOCUMENT_EXTENSIONS,
            MediaConst::ALLOWED_ARCHIVE_EXTENSIONS
        );

        return [
            // For file upload
            'file' => ['nullable', 'file', 'max:'.(MediaConst::MAX_FILE_SIZE / 1024), 'mimes:'.implode(',', $allExtensions)],

            // For folder creation
            // For Folder creation OR File from Temp
            'name' => ['required_without_all:file,key', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\s]+$/'],

            // For File from Temp (Pre-uploaded to MinIO)
            'key' => ['nullable', 'string'], // The temp key
            'original_name' => ['required_with:key', 'string', 'max:255'],
            'extension' => ['required_with:key', 'string', 'max:10'],
            'mime_type' => ['nullable', 'string'],
            'size' => ['nullable', 'numeric'],

            // Common fields
            'parent_path' => ['nullable', 'string', 'max:1000'],
            'workspace_id' => ['nullable', 'integer'],
            'is_delete' => ['nullable', Rule::enum(IsDelete::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => __('messages.file'),
            'name' => __('messages.name'),
            'parent_path' => __('messages.parent_path'),
            'workspace_id' => __('messages.workspace_id'),
            'is_delete' => __('messages.is_delete'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Folder name can only contain letters, numbers, spaces, hyphens and underscores.',
            'file.required_without' => 'Either file or folder name must be provided.',
        ];
    }
}
