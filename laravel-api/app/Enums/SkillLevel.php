<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The fixed four-step skill scale (REQ-002 Q1).
 */
enum SkillLevel: int
{
    case LEARNING = 1;
    case WITH_HELP = 2;
    case INDEPENDENT = 3;
    case CAN_TEACH = 4;

    public function label(): string
    {
        return match ($this) {
            self::LEARNING => 'Learning',
            self::WITH_HELP => 'Can use with help',
            self::INDEPENDENT => 'Independent',
            self::CAN_TEACH => 'Can teach others',
        };
    }
}
