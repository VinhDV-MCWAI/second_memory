<?php

declare(strict_types=1);

namespace App\Enums;

enum SocialStatus: int
{
    /**
     * Inactive
     */
    case INACTIVE = 0;

    /**
     * Active
     */
    case ACTIVE = 1;

    /**
     * Pending
     */
    case PENDING = 2;

    public function label(): string
    {
        return match ($this) {
            self::INACTIVE => 'Inactive',
            self::ACTIVE => 'Active',
            self::PENDING => 'Pending',
        };
    }
}
