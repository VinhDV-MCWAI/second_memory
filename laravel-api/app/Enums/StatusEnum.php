<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusEnum: int
{
    /**
     * draft
     *
     * @var int
     */
    case DRAFT = 0;

    /**
     * published
     *
     * @var int
     */
    case PUBLISHED = 1;

    /**
     * archived
     *
     * @var int
     */
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
