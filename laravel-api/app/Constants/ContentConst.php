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

    /** Levels of the category tree (a group → categories → …), the top counting as one */
    public const CATEGORY_DEPTH_MAX = 5;

    /** Levels of an article tree (a book → chapters → …), the root counting as one */
    public const ARTICLE_DEPTH_MAX = 10;

    public const PER_PAGE = 15;

    /** Articles per page of a public category */
    public const PUBLIC_PER_PAGE = 20;

    /** Articles sharing a category, beside an article that is not in a book */
    public const RELATED_MAX = 10;

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

    /**
     * View counts (ADR-0022): the public site's server reports every article page it renders, so
     * all reports come from one IP; their own limit keeps them from using up PUBLIC_RATE_PER_MINUTE.
     */
    public const PUBLIC_VIEW_RATE_PER_MINUTE = 600;

    /** One visitor opening an article again within this time counts once */
    public const VIEW_WINDOW_MINUTES = 30;

    /** Keys expire on their own there; the default (database) store would keep every expired key */
    public const VIEW_CACHE_STORE = 'redis';

    public const VIEW_CACHE_PREFIX = 'article-view:';

    /** Rows of the dashboard's "most read" and "most saved" cards */
    public const DASHBOARD_TOP = 5;

    /** The visitor is a sha256 of IP and browser, made by the public site: never stored */
    public const VISITOR_PATTERN = '/^[0-9a-f]{64}$/';

    /** Sanctum ability of the Obsidian importer's token and the token name (ADR-0010, ADR-0022). */
    public const IMPORT_ABILITY = 'article:import';

    public const IMPORT_TOKEN_NAME = 'ledger-importer';

    public const IMPORT_TOKEN_DAYS = 90;

    /** Notes in one import; tools/ledger-importer (contract.py) checks the same limits first */
    public const IMPORT_NOTES_MAX = 2000;

    /** Vault-relative path of a note */
    public const IMPORT_EXTERNAL_KEY_MAX = 500;

    public const IMPORT_RATE_PER_MINUTE = 10;

    /** `via` of the audit rows the importer writes; the actor is the token's admin */
    public const IMPORT_AUDIT_VIA = 'importer';

    /** Slug of an article whose title gives none (only symbols) */
    public const ARTICLE_SLUG_FALLBACK = 'article';
}
