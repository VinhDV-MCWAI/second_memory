// Public site (ADR-0019): HTML rendered on the server, no client JavaScript unless a page needs it.
import node from '@astrojs/node';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'astro/config';

export default defineConfig({
  // The site lives at the root; nginx sends /admin and /_next to the admin app and the rest here
  // (ADR-0021). Old addresses keep working; the skill pages are gone (ADR-0022).
  redirects: {
    '/skills': '/',
    '/skills/[slug]': '/categories/[slug]',
    '/guides/[category]': '/categories/[category]',
    '/guides/[category]/[article]': '/articles/[article]',
    '/skills/me': '/me',
    '/skills/account/login': '/account/login',
    '/skills/account/register': '/account/register',
    '/skills/files/[key]': '/files/[key]',
  },
  output: 'server',
  adapter: node({ mode: 'standalone' }),
  server: { host: '0.0.0.0', port: 4321 },
  devToolbar: { enabled: false },
  telemetry: false,
  vite: {
    plugins: [tailwindcss()],
    // Production builds bundle every dependency into dist/, so the image needs no node_modules
    ssr: { noExternal: process.env.NODE_ENV === 'production' ? true : undefined },
  },
});
