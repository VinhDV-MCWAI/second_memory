<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\SavedKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An article (by public key) and which list (ADR-0020, ADR-0022).
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
            'article' => ['required', 'string', 'uuid'],
        ];
    }
}
