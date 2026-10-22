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
    case MOST_READ = 'most_read';
    case MOST_SAVED = 'most_saved';

    /** End users who keep the article in either list (favourites, read later) */
    public const SAVE_COUNT = '(SELECT count(DISTINCT saved.end_user_id) FROM end_user_saved saved WHERE saved.article_id = article.id)';

    public function label(): string
    {
        return match ($this) {
            self::NEWEST => 'Newest',
            self::UPDATED => 'Recently updated',
            self::MOST_READ => 'Most read',
            self::MOST_SAVED => 'Most saved',
        };
    }

    /** SQL expression of the ORDER BY, largest first */
    public function expression(): string
    {
        return match ($this) {
            self::NEWEST => 'article.created_at',
            self::UPDATED => 'article.updated_at',
            self::MOST_READ => 'article.view_count',
            self::MOST_SAVED => self::SAVE_COUNT,
        };
    }
}
