// Every page path of the public site (ADR-0021: the site lives at the root, the admin app under
// /admin; ADR-0022: categories and articles). Used by pages on the server and by scripts in the browser.
const encode = encodeURIComponent;

export const VIEW_KINDS = ['articles', 'categories'] as const;
export type ViewKind = (typeof VIEW_KINDS)[number];

export const ROUTES = {
  HOME: '/',
  LOGIN: '/account/login',
  REGISTER: '/account/register',
  ME: '/me',
  SEARCH: '/search',
  /** Admin accounts sign in to the admin app, never here (ADR-0020) */
  ADMIN_LOGIN: '/admin/login',
  category: (slug: string) => `/categories/${encode(slug)}`,
  article: (slug: string) => `/articles/${encode(slug)}`,
  /** An article read from one of its categories: the reading page lists that category's articles */
  articleIn: (slug: string, category: string) => `/articles/${encode(slug)}?in=${encode(category)}`,
  /** An article opened at one of its headings (saved reading position, search result) */
  articleAt: (slug: string, anchor: string | null) =>
    `/articles/${encode(slug)}${anchor ? `#${encode(anchor)}` : ''}`,
  /** Where the reading page reports a real read (ADR-0024) */
  view: (kind: ViewKind, slug: string) => `/views/${kind}/${encode(slug)}`,
  file: (key: string) => `/files/${encode(key)}`,
} as const;
