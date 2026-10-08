<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which search mode answered (ADR-0009): the UI shows "did you mean" wording for fuzzy results.
 */
enum SearchMatch: string
{
    case EXACT = 'exact';
    case FUZZY = 'fuzzy';
}
