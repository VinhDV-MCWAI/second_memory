<?php

declare(strict_types=1);

namespace App\Enums;

enum ActionType: int
{
    case CREATE = 1;
    case UPDATE = 2;
    case DELETE = 3;

    public function label(): string
    {
        return match ($this) {
            self::CREATE => 'Create',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete',
        };
    }
}
