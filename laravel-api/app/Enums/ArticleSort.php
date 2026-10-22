<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a category page of the public site orders its articles, like the tabs of a forum (ADR-0022).
 */
enum ArticleSort: string
{
    case NEWEST = 'newest';
    case UPDATED = 'updated';

    public function label(): string
    {
        return match ($this) {
            self::NEWEST => 'Newest',
            self::UPDATED => 'Recently updated',
        };
    }

    /** Column and direction of the ORDER BY */
    public function column(): string
    {
        return match ($this) {
            self::NEWEST => 'article.created_at',
            self::UPDATED => 'article.updated_at',
        };
    }
}
