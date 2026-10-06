<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: int
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
     * waiting
     *
     * @var int
     */
    case WAITING = 2;

    /**
     * suspended
     *
     * @var int
     */
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
