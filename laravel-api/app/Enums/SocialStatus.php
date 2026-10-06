<?php

declare(strict_types=1);

namespace App\Enums;

enum SocialStatus: int
{
    /**
     * Inactive
     */
    case INACTIVE = 0;

    /**
     * Active
     */
    case ACTIVE = 1;

    /**
     * Pending
     */
    case PENDING = 2;

    public static function getLabel(self|int $value): string
    {
        if (is_int($value)) {
            $value = self::tryFrom($value);
        }

        return match ($value) {
            self::INACTIVE => 'Inactive',
            self::ACTIVE => 'Active',
            self::PENDING => 'Pending',
            default => '',
        };
    }

    public function label(): string
    {
        return self::getLabel($this);
    }
}
