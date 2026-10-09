import type { NextConfig } from 'next';
import createNextIntlPlugin from 'next-intl/plugin';

const withNextIntl = createNextIntlPlugin();

const nextConfig: NextConfig = {
  // Pages are client components ('use client') that call the API from the browser.
  // Production runs the standalone Node server (docker/nextjs/Dockerfile) behind nginx.
  output: 'standalone',

  // No image optimizer: images are served as they are
  images: {
    unoptimized: true,
  },

  // Auto-memoization (replaces most hand-written useMemo/useCallback)
  reactCompiler: true,
};

export default withNextIntl(nextConfig);
