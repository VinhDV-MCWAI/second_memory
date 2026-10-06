<?php

namespace App\Rules;

use App\Models\Management\MediaMgmt;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class IsImageMedia implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value) {
            return;
        }

        $media = MediaMgmt::find($value);

        if (! $media) {
            // Existence checking can be done by 'exists' rule, but if we query here we know.
            // If we use 'exists' rule separately, we can skip this check or just return.
            return;
        }

        if (! $media->is_file) {
            $fail('The selected media must be a file.');

            return;
        }

        // Check if mime_type starts with image/
        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            $fail('The selected media must be an image.');
        }
    }
}
