<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\SavedKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A skill (by slug) or an evidence (by public key), and which list (ADR-0020).
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
            'skill' => ['required_without:evidence', 'prohibits:evidence', 'string', 'max:'.self::SLUG_MAX],
            'evidence' => ['required_without:skill', 'string', 'uuid'],
        ];
    }
}
