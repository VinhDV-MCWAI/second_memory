// Public site (ADR-0019): HTML rendered on the server, no client JavaScript unless a page needs it.
import node from '@astrojs/node';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'astro/config';

export default defineConfig({
  // Pages and built assets live under /skills, so nginx sends one path prefix here and the rest to
  // the admin app. In dev, Vite's own files (/@vite, /src, ...) have their own nginx location.
  base: '/skills',
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
