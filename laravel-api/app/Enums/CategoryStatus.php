<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryStatus: int
{
    /**
     * inactive
     *
     * @var int
     */
    case INACTIVE = 0;

    /**
     * active
     *
     * @var int
     */
    case ACTIVE = 1;

    /**
     * archived
     *
     * @var int
     */
    case ARCHIVED = 2;

    public function label(): string
    {
        return match ($this) {
            self::INACTIVE => 'inactive',
            self::ACTIVE => 'active',
            self::ARCHIVED => 'archived',
        };
    }
}
