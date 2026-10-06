<?php

declare(strict_types=1);

namespace App\Enums;

enum FeatureStatus: int
{
    /**
     * Inactive
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

    /**
     * Get all status options as array
     */
    public static function getAll(): array
    {
        return [
            self::INACTIVE => 'Inactive',
            self::ACTIVE => 'Active',
            self::DRAFT => 'Draft',
            self::ARCHIVED => 'Archived',
        ];
    }
}
