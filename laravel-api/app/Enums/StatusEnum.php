<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusEnum: int
{
    case DRAFT = 0;
    case PUBLISHED = 1;
    case ARCHIVED = 2;

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'draft',
            self::PUBLISHED => 'published',
            self::ARCHIVED => 'archived',
        };
    }
}
