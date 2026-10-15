<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Constants\MediaConst;
use Illuminate\Foundation\Http\FormRequest;

class PartUrlsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_numbers' => ['required', 'array', 'min:1', 'max:'.MediaConst::PART_URLS_PER_REQUEST],
            'part_numbers.*' => ['required', 'integer', 'min:1', 'max:'.MediaConst::MAX_PARTS, 'distinct'],
        ];
    }
}
