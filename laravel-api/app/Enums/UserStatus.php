<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;
    case WAITING = 2;
    case SUSPENDED = 3;

    public function label(): string
    {
        return match ($this) {
            self::INACTIVE => 'inactive',
            self::ACTIVE => 'active',
            self::WAITING => 'waiting',
            self::SUSPENDED => 'suspended',
        };
    }
}
