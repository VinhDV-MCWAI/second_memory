<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\SavedKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListSavedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['kind' => ['nullable', Rule::enum(SavedKind::class)]];
    }
}
