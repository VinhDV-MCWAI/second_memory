<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\SavedKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A skill (by slug), an evidence or an article (by public key), and which list (ADR-0020, ADR-0021).
 */
class SavedRequest extends FormRequest
{
    private const SLUG_MAX = 100;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(SavedKind::class)],
            'skill' => ['required_without_all:evidence,article', 'prohibits:evidence,article', 'string', 'max:'.self::SLUG_MAX],
            'evidence' => ['required_without_all:skill,article', 'prohibits:article', 'string', 'uuid'],
            'article' => ['required_without_all:skill,evidence', 'string', 'uuid'],
        ];
    }
}
