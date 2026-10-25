<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What an end user saved (ADR-0024).
 */
enum SavedTarget: string
{
    case ARTICLE = 'article';
    case CATEGORY = 'category';

    public function label(): string
    {
        return match ($this) {
            self::ARTICLE => 'Article',
            self::CATEGORY => 'Category',
        };
    }
}
