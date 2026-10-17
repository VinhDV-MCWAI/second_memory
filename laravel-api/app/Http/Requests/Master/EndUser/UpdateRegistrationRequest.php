<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\EndUser;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['open' => ['required', 'boolean']];
    }
}
