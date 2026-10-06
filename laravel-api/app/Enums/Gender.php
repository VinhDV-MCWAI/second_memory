<?php

declare(strict_types=1);

namespace App\Enums;

enum Gender: int
{
    /**
     * male
     *
     * @var int
     */
    /**
     * male
     *
     * @var int
     */
    case MALE = 1;

    /**
     * female
     *
     * @var int
     */
    case FEMALE = 2;

    /**
     * other
     *
     * @var int
     */
    case OTHER = 3;

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'male',
            self::FEMALE => 'female',
            self::OTHER => 'other',
        };
    }
}
