<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The admin search box (ADR-0009).
 */
final class SearchConst
{
    public const QUERY_MIN = 2;

    public const QUERY_MAX = 100;

    /** Results per group of the admin search (ADR-0008). */
    public const LIMIT = 10;

    public const SNIPPET_LENGTH = 160;

    /** `pg_trgm.word_similarity_threshold` of the typo fallback (ADR-0009: transpositions score ~0.47). */
    public const FUZZY_THRESHOLD = '0.4';

    /**
     * ts_rank weights for the {D, C, B, A} labels of `search_tsv` (A title / name, B summary,
     * C body). Steeper than the default {0.1, 0.2, 0.4, 1.0}, whose ranks saturate near 1 so a
     * summary repeating the words almost ties with a title match (BUG-04).
     */
    public const RANK_WEIGHTS = '{0.05, 0.1, 0.2, 1.0}';
}
