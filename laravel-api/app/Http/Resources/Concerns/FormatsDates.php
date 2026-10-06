<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

use App\Constants\CommonVal;

trait FormatsDates
{
    /**
     * Format a timestamp as CommonVal::DATE_FORMAT. A missing value prints the Unix epoch
     * (01/01/1970) — kept as-is because clients already receive it.
     */
    protected function formatDate(mixed $value): string
    {
        return date(CommonVal::DATE_FORMAT, (int) strtotime((string) $value));
    }
}
