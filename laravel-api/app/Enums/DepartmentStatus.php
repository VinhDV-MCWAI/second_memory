<?php

declare(strict_types=1);

namespace App\Enums;

enum DepartmentStatus: int
{
    /**
     * inactive
     *
     * @var int
     */
    case INACTIVE = 0;

    /**
     * Active
     *
     * @var int
     */
    case ACTIVE = 1;

    /**
     * Draft
     *
     * @var int
     */
    case DRAFT = 2;

    /**
     * Archived
     *
     * @var int
     */
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
