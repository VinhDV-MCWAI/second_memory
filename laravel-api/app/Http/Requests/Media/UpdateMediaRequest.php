<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Rename (`name`) and/or move (`folder`) a file or a folder.
 */
class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folder' => MediaRules::folder(),
            'name' => ['nullable', ...MediaRules::name()],
        ];
    }

    public function attributes(): array
    {
        return ['folder' => __('messages.folder'), 'name' => __('messages.name')];
    }
}
