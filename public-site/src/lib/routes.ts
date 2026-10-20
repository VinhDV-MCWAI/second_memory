// Every page path of the public site (ADR-0021: the site lives at the root, the admin app under
// /admin; ADR-0022: categories and articles). Used by pages on the server and by scripts in the browser.
const encode = encodeURIComponent;

export const ROUTES = {
  HOME: '/',
  LOGIN: '/account/login',
  REGISTER: '/account/register',
  ME: '/me',
  /** Admin accounts sign in to the admin app, never here (ADR-0020) */
  ADMIN_LOGIN: '/admin/login',
  category: (slug: string) => `/categories/${encode(slug)}`,
  article: (slug: string) => `/articles/${encode(slug)}`,
  file: (key: string) => `/files/${encode(key)}`,
} as const;
