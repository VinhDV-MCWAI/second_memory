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

    /** Deleted files stay in the trash this long; docker/minio/ilm-history.json expires old versions after the same days. */
    public const TRASH_DAYS = 30;

    /** A restored file whose name is taken meanwhile becomes `name (restored).ext`, then `name (restored 2).ext`, … */
    public const RESTORED_SUFFIX = 'restored';

    /** Lifetime of a presigned upload or download URL. */
    public const UPLOAD_URL_TTL_SECONDS = 300;

    public const DOWNLOAD_URL_TTL_SECONDS = 300;

    /** A preview link outlives a download link: a video keeps fetching ranges while it plays. */
    public const PREVIEW_URL_TTL_SECONDS = 3600;

    /** Text files are previewed in full, so only small ones. */
    public const PREVIEW_TEXT_MAX_BYTES = 2 * 1024 * 1024;

    private const TEXT = 'text/plain; charset=utf-8';

    /**
     * Previewable extensions => content type served (PreviewKind). No HTML, SVG or XML: they can run
     * script on the admin's origin. Source code and data files are shown as plain text.
     */
    public const PREVIEW_CONTENT_TYPES = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
        'webp' => 'image/webp', 'avif' => 'image/avif', 'bmp' => 'image/bmp',
        'pdf' => 'application/pdf',
        'mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'webm' => 'video/webm', 'ogv' => 'video/ogg',
        'mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'oga' => 'audio/ogg', 'flac' => 'audio/flac',
        'txt' => self::TEXT, 'md' => self::TEXT, 'csv' => self::TEXT, 'tsv' => self::TEXT, 'log' => self::TEXT,
        'json' => self::TEXT, 'yml' => self::TEXT, 'yaml' => self::TEXT, 'ini' => self::TEXT, 'env' => self::TEXT,
        'sql' => self::TEXT, 'js' => self::TEXT, 'ts' => self::TEXT, 'py' => self::TEXT, 'java' => self::TEXT,
        'go' => self::TEXT, 'rs' => self::TEXT, 'css' => self::TEXT,
    ];

    /** Files a browser or server could run; refused by extension, case-insensitive. */
    public const BLOCKED_EXTENSIONS = ['exe', 'msi', 'bat', 'cmd', 'com', 'scr', 'ps1', 'sh', 'php', 'phtml', 'phar'];

    /** A name is one path segment: no slashes or control characters, not `.` or `..`. */
    public const NAME_PATTERN = '/^(?!\.{1,2}$)[^\/\\\\\x00-\x1F\x7F]+$/u';

    public const PER_PAGE = 50;
}
