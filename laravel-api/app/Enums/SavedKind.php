<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lists an end user keeps on the public site (ADR-0020).
 */
enum SavedKind: string
{
    case FAVORITE = 'favorite';
    case READ_LATER = 'read_later';

    public function label(): string
    {
        return match ($this) {
            self::FAVORITE => 'Favorites',
            self::READ_LATER => 'Read later',
        };
    }
}
