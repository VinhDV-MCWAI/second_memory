<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class DownloadUrlRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** 1: a preview link (shown in the browser) instead of a download */
            'inline' => ['sometimes', 'boolean'],
        ];
    }
}
