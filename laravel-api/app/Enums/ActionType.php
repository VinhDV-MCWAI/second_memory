<?php

declare(strict_types=1);

namespace App\Enums;

enum ActionType: int
{
    /**
     * Create
     *
     * @var int
     */
    case CREATE = 1;

    /**
     * Update
     *
     * @var int
     */
    case UPDATE = 2;

    /**
     * Delete
     *
     * @var int
     */
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
