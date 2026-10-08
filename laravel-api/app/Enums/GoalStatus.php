<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Learning goal lifecycle (REQ-002 Q2): open goals become achieved when the skill reaches the target level.
 */
enum GoalStatus: string
{
    case OPEN = 'open';
    case ACHIEVED = 'achieved';
    case DROPPED = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'open',
            self::ACHIEVED => 'achieved',
            self::DROPPED => 'dropped',
        };
    }
}
