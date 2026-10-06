<?php

declare(strict_types=1);

namespace App\Enums;

enum IsDisplay: int
{
    case FALSE = 0;
    case TRUE = 1;

    public function label(): string
    {
        return match ($this) {
            self::FALSE => 'false',
            self::TRUE => 'true',
        };
    }
}
