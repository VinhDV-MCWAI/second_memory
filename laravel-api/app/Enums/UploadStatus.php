<?php

namespace App\Enums;

enum UploadStatus: int
{
    case PROCESSING = 1;
    case COMPLETED = 2;
    case FAILED = 3;

    public function label(): string
    {
        return match ($this) {
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }
}
