<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Skill Ledger field limits and formats (ADR-0008 contract table).
 */
final class LedgerConst
{
    /** Request and response date format of the Skill Ledger API (ISO 8601 date). */
    public const DATE_FORMAT = 'Y-m-d';

    public const SKILL_NAME_MAX = 100;

    public const SKILL_CATEGORY_MAX = 50;

    public const SKILL_DESCRIPTION_MAX = 2000;

    public const LEVEL_REASON_MAX = 500;

    public const TAG_NAME_MAX = 50;

    public const PER_PAGE = 15;
}
