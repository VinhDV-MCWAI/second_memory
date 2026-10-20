<?php

declare(strict_types=1);

namespace App\Http\Requests\Content;

use App\Enums\MoveDirection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One step up or down among the row's siblings (ADR-0022).
 */
class MoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['direction' => ['required', Rule::enum(MoveDirection::class)]];
    }

    public function attributes(): array
    {
        return ['direction' => __('messages.direction')];
    }
}
