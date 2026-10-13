<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Constants\MediaConst;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Every part with the ETag MinIO returned for it.
 */
class CompleteMultipartUploadRequest extends FormRequest
{
    private const ETAG_MAX = 100;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parts' => ['required', 'array', 'min:1', 'max:'.MediaConst::MAX_PARTS],
            'parts.*.part_number' => ['required', 'integer', 'min:1', 'max:'.MediaConst::MAX_PARTS, 'distinct'],
            'parts.*.etag' => ['required', 'string', 'max:'.self::ETAG_MAX],
        ];
    }
}
