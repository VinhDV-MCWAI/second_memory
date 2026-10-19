/**
 * Categories and articles of the public home page (ADR-0021), as the list endpoints return them.
 * Generated from laravel-api/openapi.json (`pnpm gen:api`); do not hand-edit fields here.
 */
import type { components } from '@/shared/types/openapi';

type Schemas = components['schemas'];

export type ContentCategory = Schemas['ContentCategoryResource'];
export type Article = Schemas['ArticleResource'];
