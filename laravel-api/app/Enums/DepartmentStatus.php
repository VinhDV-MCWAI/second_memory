<?php

declare(strict_types=1);

namespace App\Enums;

enum DepartmentStatus: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;
    case DRAFT = 2;
    case ARCHIVED = 3;

    public function label(): string
    {
        return match ($this) {
            self::INACTIVE => 'Inactive',
            self::ACTIVE => 'Active',
            self::DRAFT => 'Draft',
            self::ARCHIVED => 'Archived',
        };
    }
}
