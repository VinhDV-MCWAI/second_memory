<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The two admin roles (ADR-0005): an owner may change data, a viewer may only read it.
 */
enum AdminRole: string
{
    case OWNER = 'owner';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'owner',
            self::VIEWER => 'viewer',
        };
    }
}
