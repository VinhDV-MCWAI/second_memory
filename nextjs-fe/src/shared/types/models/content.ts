/**
 * Categories and articles of the public home page (ADR-0021), as the list endpoints return them.
 * Generated from laravel-api/openapi.json (`pnpm gen:api`); do not hand-edit fields here.
 */
import type { components, operations } from '@/shared/types/openapi';
import type { OkData } from './ok-data';

type Schemas = components['schemas'];

export type ContentCategory = Schemas['ContentCategoryResource'];
export type Article = Schemas['ArticleResource'];

export type DashboardSummary = OkData<operations['dashboard.summary']>;
export type SearchResult = OkData<operations['search.query']>;
