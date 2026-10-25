// Server-side client for the public API (docs/features/trang-public.md).
// Types come from laravel-api/openapi.json (`pnpm gen:api`, run by `make openapi`).
import { toSearch } from '@/lib/category-query';
import type { components, paths } from '@/types/openapi';

// Inside Docker the browser URL (localhost:81) is not reachable, so the server calls nginx on the
// Compose network (unprivileged nginx listens on 8080 inside).
const DEFAULT_API_URL = 'http://ml-nginx:8080/api';
const API_URL = process.env.API_INTERNAL_URL ?? DEFAULT_API_URL;

// The public API is rate-limited per IP and every request comes from this server's IP,
// so answers are kept for a minute instead of fetched once per visitor.
const CACHE_MS = 60_000;

const HTTP_NOT_FOUND = 404;

type Data<P extends keyof paths> = paths[P] extends {
  get: { responses: { 200: { content: { 'application/json': { data: infer D } } } } };
}
  ? D
  : never;

export type PublicGroup = components['schemas']['PublicGroupResource'];
export type PublicCategoryDetail = components['schemas']['PublicCategoryDetailResource'];
export type PublicArticle = components['schemas']['PublicArticleResource'];
export type PublicAttachment = components['schemas']['PublicAttachmentResource'];
export type ArticleSort = components['schemas']['ArticleSort'];
export type PublicSearch = Data<'/public/search'>;
export type PublicCategoryNode = Data<'/public/categories'>[number];
export type PublicArticleCard = components['schemas']['PublicArticleCardResource'];
export type BookNode = NonNullable<PublicArticle['book']>;
export type CategoryQuery = NonNullable<
  paths['/public/categories/{slug}']['get']['parameters']['query']
>;

const cache = new Map<string, { at: number; data: unknown }>();

// null when the API answers 404 (private or missing: the API does not tell them apart).
async function getPublic<T>(path: string): Promise<T | null> {
  const hit = cache.get(path);
  if (hit && Date.now() - hit.at < CACHE_MS) {
    return hit.data as T | null;
  }
  const response = await fetch(`${API_URL}/public/${path}`, {
    headers: { Accept: 'application/json' },
  });
  if (!response.ok && response.status !== HTTP_NOT_FOUND) {
    throw new Error(`Public API ${path} answered ${response.status}`);
  }
  const data =
    response.status === HTTP_NOT_FOUND ? null : ((await response.json()) as { data: T }).data;
  cache.set(path, { at: Date.now(), data });
  return data;
}

export async function getPublicHome(): Promise<PublicGroup[]> {
  return (await getPublic<Data<'/public/home'>>('home'))?.data ?? [];
}

// Every public category in tree order, with its level, article count, views and favourites
export async function getPublicCategories(): Promise<PublicCategoryNode[]> {
  return (await getPublic<Data<'/public/categories'>>('categories')) ?? [];
}

// The first page of every public article in one order (the tabs of the home page)
export async function getPublicArticles(
  sort: ArticleSort,
  perPage: number,
): Promise<PublicArticleCard[]> {
  const path = `articles${toSearch({ sort, per_page: perPage })}`;
  return (await getPublic<Data<'/public/articles'>>(path))?.data.articles ?? [];
}

// The query must already be valid (the page checks it): the API answers 422 otherwise.
export async function getPublicCategory(
  slug: string,
  query: CategoryQuery = {},
): Promise<PublicCategoryDetail | null> {
  const path = `categories/${encodeURIComponent(slug)}${toSearch(query)}`;
  return (await getPublic<Data<'/public/categories/{slug}'>>(path))?.data ?? null;
}

export async function getPublicArticle(slug: string): Promise<PublicArticle | null> {
  const path = `articles/${encodeURIComponent(slug)}`;
  return (await getPublic<Data<'/public/articles/{slug}'>>(path))?.data ?? null;
}

// Short-lived MinIO link of an attached file; null when the file is not on the public site.
// Kept for less time than the link lives (5 minutes for a download, 1 hour for a preview).
export async function getPublicFileUrl(key: string): Promise<string | null> {
  return (
    (await getPublic<Data<'/public/files/{key}'>>(`files/${encodeURIComponent(key)}`))?.url ?? null
  );
}

export async function getPublicSearch(q: string): Promise<PublicSearch | null> {
  return getPublic<PublicSearch>(`search?q=${encodeURIComponent(q)}`);
}

// A view is counted by the API once per visitor and article per 30 minutes (ADR-0022). Never awaited
// by the page and never fails it: a slow or broken API only loses a view.
const VIEW_TIMEOUT_MS = 2_000;

export function reportView(slug: string, visitor: string): void {
  fetch(`${API_URL}/public/articles/${encodeURIComponent(slug)}/view`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ visitor }),
    signal: AbortSignal.timeout(VIEW_TIMEOUT_MS),
  }).catch(() => undefined);
}
