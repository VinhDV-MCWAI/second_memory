<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use App\Constants\BackgroundTaskConst;
use Illuminate\Foundation\Http\FormRequest;

class TestBackgroundTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seconds' => ['nullable', 'integer', 'min:0', 'max:'.BackgroundTaskConst::TEST_MAX_SECONDS],
            'fail' => ['nullable', 'boolean'],
        ];
    }
}
