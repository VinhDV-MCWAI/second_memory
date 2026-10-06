<?php

declare(strict_types=1);

namespace App\Enums;

enum IsDelete: int
{
    /**
     * false
     *
     * @var int
     */
    case FALSE = 0;

    /**
     * true
     *
     * @var int
     */
    case TRUE = 1;

    public static function getLabel(self|int $value): string
    {
        if (is_int($value)) {
            $value = self::tryFrom($value);
        }

        return match ($value) {
            self::FALSE => 'false',
            self::TRUE => 'true',
            default => '',
        };
    }

    public function label(): string
    {
        return self::getLabel($this);
    }
}
