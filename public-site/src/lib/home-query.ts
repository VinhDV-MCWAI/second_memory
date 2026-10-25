// The two choices of the home page in its address (REQ-006): ?categories=…&articles=…. Plain links,
// so they work without JavaScript; an unknown value falls back to the first choice.
import type { ArticleSort, PublicCategoryNode } from '@/lib/public-api';
import { ROUTES } from '@/lib/routes';

export const CATEGORY_SORTS = [
  { value: 'order', label: 'In order' },
  { value: 'most_viewed', label: 'Most viewed' },
  { value: 'most_favorited', label: 'Most favorited' },
] as const;

export type CategorySort = (typeof CATEGORY_SORTS)[number]['value'];

/** Tabs of the article list, in order; the first is the default */
export const ARTICLE_TABS: { value: ArticleSort; label: string }[] = [
  { value: 'most_read', label: 'Most read' },
  { value: 'most_favorited', label: 'Most favorited' },
  { value: 'newest', label: 'Newest' },
];

/** Articles in each tab */
export const ARTICLE_TAB_SIZE = 10;
/** Category badges when sorted by a count; "In order" shows every category under its group */
export const CATEGORY_TOP_SIZE = 24;

/** Ids of the two sections, so a link lands back on the section it changed */
export const SECTION_CATEGORIES = 'categories';
export const SECTION_ARTICLES = 'articles';

export interface HomeQuery {
  categories: CategorySort;
  articles: ArticleSort;
}

export function readHomeQuery(params: URLSearchParams): HomeQuery {
  const categories = CATEGORY_SORTS.find(({ value }) => value === params.get('categories'));
  const articles = ARTICLE_TABS.find(({ value }) => value === params.get('articles'));
  return {
    categories: (categories ?? CATEGORY_SORTS[0]).value,
    articles: (articles ?? ARTICLE_TABS[0]!).value,
  };
}

/** Address of the home page with one choice changed; defaults are left out of it */
export function homeUrl(query: HomeQuery, change: Partial<HomeQuery>, section: string): string {
  const next = { ...query, ...change };
  const search = new URLSearchParams();
  if (next.categories !== CATEGORY_SORTS[0].value) {
    search.set('categories', next.categories);
  }
  if (next.articles !== ARTICLE_TABS[0]!.value) {
    search.set('articles', next.articles);
  }
  const text = search.toString();
  return `${ROUTES.HOME}${text ? `?${text}` : ''}#${section}`;
}

/** Categories below the groups, largest count first; ties keep the owner's order (the sort is stable) */
export function sortCategories(
  categories: PublicCategoryNode[],
  sort: Exclude<CategorySort, 'order'>,
): PublicCategoryNode[] {
  const count = (category: PublicCategoryNode) =>
    sort === 'most_viewed' ? category.view_count : category.favorite_count;
  return [...categories].sort((a, b) => count(b) - count(a)).slice(0, CATEGORY_TOP_SIZE);
}
