<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Categories and articles of the public site (ADR-0021, ADR-0022).
 */
final class ContentConst
{
    public const CATEGORY_NAME_MAX = 100;

    /** Markdown, shown on the category page */
    public const CATEGORY_DESCRIPTION_MAX = 20_000;

    public const ATTACHMENTS_MAX = 20;

    public const ATTACHMENT_TITLE_MAX = 200;

    public const ATTACHMENT_URL_MAX = 2048;

    public const ATTACHMENT_NOTE_MAX = 500;

    public const ARTICLE_TITLE_MAX = 200;

    public const ARTICLE_SUMMARY_MAX = 500;

    /** Markdown source of one article */
    public const ARTICLE_BODY_MAX = 100_000;

    /** Levels of an article tree (a book → chapters → …), the root counting as one */
    public const ARTICLE_DEPTH_MAX = 10;

    public const PER_PAGE = 15;

    /** Articles embedded in articles: `![[a]]` inside `![[b]]` inside … (ADR-0022) */
    public const EMBED_DEPTH_MAX = 3;

    /** Paths of the public site (public-site/src/lib/routes.ts) written into rendered HTML */
    public const PUBLIC_ARTICLE_PATH = '/articles/';

    public const PUBLIC_FILE_PATH = '/files/';

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
