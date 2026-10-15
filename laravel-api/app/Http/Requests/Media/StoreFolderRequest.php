<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
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
        ];
    }

    public function attributes(): array
    {
        return ['folder' => __('messages.folder'), 'name' => __('messages.name')];
    }
}
