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

    /** Largest file the file manager accepts (multipart upload). */
    public const MAX_FILE_BYTES = 10 * 1024 * 1024 * 1024;

    /** S3 limits: every part but the last is at least 5 MiB, at most 10,000 parts per object. */
    public const MIN_PART_BYTES = 5 * 1024 * 1024;

    public const MAX_PARTS = 10000;

    /**
     * Part size by file size (minimum part size => files up to this size): bigger parts for bigger
     * files keep the number of requests reasonable. Never below size / MAX_PARTS.
     */
    public const PART_SIZE_STEPS = [
        16 * 1024 * 1024 => 500 * 1024 * 1024,
        32 * 1024 * 1024 => self::MAX_FILE_BYTES,
    ];

    /** Part URLs are handed out in batches, as the browser gets to them, so none expires while waiting. */
    public const PART_URLS_PER_REQUEST = 100;

    public const PART_URL_TTL_SECONDS = 600;

    /** Uploads not finished after this long are aborted by `media:clean-stuck-uploads`. */
    public const STUCK_UPLOAD_HOURS = 24;

    /** Background task type of the post-upload processing (BackgroundTaskFinished.type). */
    public const TASK_PROCESS = 'media.process';

    /** Lifetime of a presigned upload or download URL. */
    public const UPLOAD_URL_TTL_SECONDS = 300;

    public const DOWNLOAD_URL_TTL_SECONDS = 300;

    /** Files a browser or server could run; refused by extension, case-insensitive. */
    public const BLOCKED_EXTENSIONS = ['exe', 'msi', 'bat', 'cmd', 'com', 'scr', 'ps1', 'sh', 'php', 'phtml', 'phar'];

    /** A name is one path segment: no slashes or control characters, not `.` or `..`. */
    public const NAME_PATTERN = '/^(?!\.{1,2}$)[^\/\\\\\x00-\x1F\x7F]+$/u';

    public const PER_PAGE = 50;
}
