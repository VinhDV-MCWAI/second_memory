<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Constants\ContentConst;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A view of an article reported by the public site's server (ADR-0022).
 */
class PublicViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /** sha256 (hex) of the visitor's IP and browser, made by the public site; only kept as a cache key for 30 minutes */
            'visitor' => ['required', 'string', 'regex:'.ContentConst::VISITOR_PATTERN],
        ];
    }
}
