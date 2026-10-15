<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Constants\MediaConst;
use App\Constants\Messages;
use Closure;
use Illuminate\Support\Str;

/**
 * Field rules shared by the file manager requests (ADR-0014).
 */
final class MediaRules
{
    /** `/` or `/a/b`: absolute, no empty segments, no trailing slash. */
    private const FOLDER_PATTERN = '/^\/(?:[^\/\x00-\x1F\x7F]+(?:\/[^\/\x00-\x1F\x7F]+)*)?$/u';

    /**
     * @return list<string>
     */
    public static function folder(): array
    {
        return ['nullable', 'string', 'max:'.MediaConst::PATH_MAX, 'regex:'.self::FOLDER_PATTERN];
    }

    /**
     * One path segment whose extension is not a runnable file type (also checked on rename).
     *
     * @return list<string|Closure>
     */
    public static function name(): array
    {
        return [
            'string',
            'max:'.MediaConst::NAME_MAX,
            'regex:'.MediaConst::NAME_PATTERN,
            static function (string $attribute, mixed $value, Closure $fail): void {
                $extension = Str::lower(pathinfo((string) $value, PATHINFO_EXTENSION));
                if (in_array($extension, MediaConst::BLOCKED_EXTENSIONS, true)) {
                    $fail(Messages::E0035);
                }
            },
        ];
    }
}
