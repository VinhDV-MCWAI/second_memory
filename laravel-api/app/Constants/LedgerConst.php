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

    public const EVIDENCE_TITLE_MAX = 200;

    public const EVIDENCE_URL_MAX = 2048;

    public const EVIDENCE_SUMMARY_MAX = 1000;

    public const GOAL_NOTE_MAX = 1000;

    public const PER_PAGE = 15;

    public const SEARCH_QUERY_MIN = 2;

    public const SEARCH_QUERY_MAX = 100;

    /** Results per group (skills, goals, evidence) of the admin search (ADR-0008). */
    public const SEARCH_LIMIT = 10;

    public const SEARCH_SNIPPET_LENGTH = 160;

    /** `pg_trgm.word_similarity_threshold` of the typo fallback (ADR-0009: transpositions score ~0.47). */
    public const SEARCH_FUZZY_THRESHOLD = '0.4';

    /** Requests per minute and IP on /api/public (RFC-002 §4.3). */
    public const PUBLIC_RATE_PER_MINUTE = 60;
}
