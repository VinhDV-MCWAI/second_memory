<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * One step up or down among siblings: the ↑ ↓ buttons that replace typed order numbers (ADR-0022).
 */
enum MoveDirection: string
{
    case UP = 'up';
    case DOWN = 'down';

    public function label(): string
    {
        return match ($this) {
            self::UP => 'Up',
            self::DOWN => 'Down',
        };
    }
}
