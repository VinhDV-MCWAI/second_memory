// Every page path of the public site (ADR-0021: the site lives at the root, the admin app under
// /admin). Used by pages on the server and by scripts in the browser.
const encode = encodeURIComponent;

export const ROUTES = {
  HOME: '/',
  SKILLS: '/skills',
  LOGIN: '/account/login',
  REGISTER: '/account/register',
  ME: '/me',
  skill: (slug: string) => `/skills/${encode(slug)}`,
  category: (slug: string) => `/guides/${encode(slug)}`,
  article: (category: string, slug: string) => `/guides/${encode(category)}/${encode(slug)}`,
  file: (key: string) => `/files/${encode(key)}`,
} as const;
