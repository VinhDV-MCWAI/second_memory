// The article list of a category page in its address (ADR-0022): ?sort=&also=&q=&page=. Read from
// the visitor's URL, checked here so a hand-edited address never makes the API answer 422.
import type { ArticleSort, CategoryQuery } from '@/lib/public-api';
import { ROUTES } from '@/lib/routes';

/** Tabs of the list, in order; the first is the default */
export const ARTICLE_SORTS: { value: ArticleSort; label: string }[] = [
  { value: 'newest', label: 'Newest' },
  { value: 'updated', label: 'Recently updated' },
];

// Limits of the API (ContentConst: CATEGORY_NAME_MAX, ARTICLE_TITLE_MAX)
const SLUG_MAX = 100;
export const WORDS_MAX = 200;
const PAGE_PATTERN = /^[1-9]\d{0,5}$/;

export function readCategoryQuery(params: URLSearchParams): CategoryQuery {
  const sort = ARTICLE_SORTS.find(({ value }) => value === params.get('sort'))?.value;
  const page = params.get('page') ?? '';
  return {
    sort: sort ?? null,
    also: params.get('also')?.slice(0, SLUG_MAX) || null,
    q: params.get('q')?.trim().slice(0, WORDS_MAX) || null,
    page: PAGE_PATTERN.test(page) ? Number(page) : null,
  };
}

/** `?sort=…&page=…` of the values that are set, or '' */
export function toSearch(query: CategoryQuery): string {
  const search = new URLSearchParams(
    Object.entries(query).flatMap(([name, value]) =>
      value === null || value === undefined || value === '' ? [] : [[name, String(value)]],
    ),
  ).toString();
  return search ? `?${search}` : '';
}

/** Address of the same list with some values changed; null drops one */
export function categoryUrl(slug: string, query: CategoryQuery, change: CategoryQuery): string {
  return `${ROUTES.category(slug)}${toSearch({ ...query, ...change })}`;
}
