<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Life of a `media_mgmt` row (ADR-0014): a file row is created when the upload URL is handed out.
 */
enum UploadStatus: int
{
    case UPLOADING = 0;
    case PROCESSING = 1;
    case COMPLETED = 2;
    case FAILED = 3;

    public function label(): string
    {
        return match ($this) {
            self::UPLOADING => 'uploading',
            self::PROCESSING => 'processing',
            self::COMPLETED => 'completed',
            self::FAILED => 'failed',
        };
    }
}
