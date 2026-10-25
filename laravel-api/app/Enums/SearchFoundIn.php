<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where the public search found an article (ADR-0024): the site tags the result with it.
 */
enum SearchFoundIn: string
{
    /** Title or summary */
    case TITLE = 'title';
    /** Only the body */
    case CONTENT = 'content';

    public function label(): string
    {
        return match ($this) {
            self::TITLE => 'Article',
            self::CONTENT => 'Content',
        };
    }
}
