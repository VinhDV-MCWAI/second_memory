<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryStatus: int
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
     * archived
     *
     * @var int
     */
    case ARCHIVED = 2;

    public static function getLabel(self|int $value): string
    {
        if (is_int($value)) {
            $value = self::tryFrom($value);
        }

        return match ($value) {
            self::INACTIVE => 'inactive',
            self::ACTIVE => 'active',
            self::ARCHIVED => 'archived',
            default => '',
        };
    }

    public function label(): string
    {
        return self::getLabel($this);
    }
}
