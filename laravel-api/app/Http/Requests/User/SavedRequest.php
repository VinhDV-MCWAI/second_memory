<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Constants\ContentConst;
use App\Enums\SavedKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Which list, and either an article (by public key) or a category (by slug; favourites only)
 * (ADR-0020, ADR-0022, ADR-0024).
 */
class SavedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(SavedKind::class)],
            /** Public key of an article */
            'article' => ['nullable', 'string', 'uuid', 'required_without:category', 'prohibits:category'],
            /** Slug of a category: favourites only */
            'category' => [
                'nullable',
                'string',
                'max:'.ContentConst::CATEGORY_NAME_MAX,
                'required_without:article',
                Rule::prohibitedIf(fn (): bool => $this->input('kind') !== SavedKind::FAVORITE->value),
            ],
        ];
    }
}
