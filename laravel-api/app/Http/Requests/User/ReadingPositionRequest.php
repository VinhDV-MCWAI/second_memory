<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Constants\ContentConst;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The heading a reader reached in a read-later article (ADR-0024); no anchor = the top.
 */
class ReadingPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /** Public key of the article */
            'article' => ['required', 'string', 'uuid'],
            /** Id of the heading (`toc[].id` of the article) */
            'anchor' => ['nullable', 'string', 'max:'.ContentConst::READING_ANCHOR_MAX],
        ];
    }
}
