<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * File management limits (ADR-0014).
 */
final class MediaConst
{
    /** Virtual path of the root folder; every other path is `/a/b/name`. */
    public const ROOT = '/';

    public const PATH_SEPARATOR = '/';

    public const NAME_MAX = 255;

    public const PATH_MAX = 1000;

    public const MIME_TYPE_MAX = 100;

    /** Files up to this size go up in one PUT; larger ones use multipart upload. */
    public const SINGLE_UPLOAD_MAX_BYTES = 100 * 1024 * 1024;

    /** Lifetime of a presigned upload or download URL. */
    public const UPLOAD_URL_TTL_SECONDS = 300;

    public const DOWNLOAD_URL_TTL_SECONDS = 300;

    /** Files a browser or server could run; refused by extension, case-insensitive. */
    public const BLOCKED_EXTENSIONS = ['exe', 'msi', 'bat', 'cmd', 'com', 'scr', 'ps1', 'sh', 'php', 'phtml', 'phar'];

    /** A name is one path segment: no slashes or control characters, not `.` or `..`. */
    public const NAME_PATTERN = '/^(?!\.{1,2}$)[^\/\\\\\x00-\x1F\x7F]+$/u';

    public const PER_PAGE = 50;
}
