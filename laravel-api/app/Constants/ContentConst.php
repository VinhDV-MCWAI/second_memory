<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Categories and articles of the public site (ADR-0021).
 */
final class ContentConst
{
    public const CATEGORY_NAME_MAX = 100;

    public const CATEGORY_DESCRIPTION_MAX = 500;

    public const ARTICLE_TITLE_MAX = 200;

    public const ARTICLE_SUMMARY_MAX = 500;

    /** Markdown source of one article */
    public const ARTICLE_BODY_MAX = 100_000;

    public const SORT_ORDER_MAX = 9999;

    public const PER_PAGE = 15;

    /** audit_log.auditable_type */
    public const CATEGORY_AUDITABLE_TYPE = 'content_category';

    public const ARTICLE_AUDITABLE_TYPE = 'article';

    /** Requests per minute and IP on /api/public (RFC-002 §4.3). */
    public const PUBLIC_RATE_PER_MINUTE = 60;

    /** Sanctum ability of the Obsidian importer's token and the token name (ADR-0010). */
    public const IMPORT_ABILITY = 'evidence:import';

    public const IMPORT_TOKEN_NAME = 'ledger-importer';

    public const IMPORT_TOKEN_DAYS = 90;
}
