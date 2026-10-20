/**
 * Groups, categories and articles of the public site (ADR-0022), as the list endpoints return them.
 * Generated from laravel-api/openapi.json (`pnpm gen:api`); do not hand-edit fields here.
 */
import type { components, operations } from '@/shared/types/openapi';
import type { OkData } from './ok-data';

type Schemas = components['schemas'];

export type ContentCategory = Schemas['ContentCategoryResource'];
/** A link or a file attached to a category as its evidence. */
export type CategoryAttachment = ContentCategory['attachments'][number];
/** A file of the file manager attached to content: the Files view of it, plus `available` and `locked`. */
export type AttachedFile = Schemas['AttachedFileResource'];
export type Article = Schemas['ArticleResource'];

/** `article/preview`: Markdown as the public page shows it, with its table of contents. */
export type MarkdownPreview = OkData<operations['article.preview']>;

export type DashboardSummary = OkData<operations['dashboard.summary']>;
export type SearchResult = OkData<operations['search.query']>;
