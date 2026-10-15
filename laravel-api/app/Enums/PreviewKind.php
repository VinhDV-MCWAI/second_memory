<?php

declare(strict_types=1);

namespace App\Enums;

use App\Constants\MediaConst;

/**
 * How the Files screen shows a file without downloading it (MEDIA-06). Only types a browser renders
 * without running code: files are served from the admin's own origin (nginx :81), so HTML or SVG
 * shown inline could act as the signed-in admin.
 */
enum PreviewKind: string
{
    case IMAGE = 'image';
    case PDF = 'pdf';
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case TEXT = 'text';

    /** The kind for a stored file, or null when it is not previewed (unknown type, text too large). */
    public static function forFile(?string $extension, ?int $size): ?self
    {
        $contentType = self::contentType($extension);
        if ($contentType === null) {
            return null;
        }
        $kind = match (true) {
            str_starts_with($contentType, 'image/') => self::IMAGE,
            str_starts_with($contentType, 'video/') => self::VIDEO,
            str_starts_with($contentType, 'audio/') => self::AUDIO,
            str_starts_with($contentType, 'text/') => self::TEXT,
            default => self::PDF,
        };

        return $kind === self::TEXT && ($size ?? 0) > MediaConst::PREVIEW_TEXT_MAX_BYTES ? null : $kind;
    }

    /** Content type the preview is served with, chosen by extension (never the type the browser sent). */
    public static function contentType(?string $extension): ?string
    {
        return MediaConst::PREVIEW_CONTENT_TYPES[strtolower((string) $extension)] ?? null;
    }
}
