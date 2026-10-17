<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Departments (ADR-0017): field limits.
 */
final class DepartmentConst
{
    public const CODE_MAX = 30;

    public const NAME_MAX = 100;

    public const DESCRIPTION_MAX = 1000;

    public const PER_PAGE = 15;
}
