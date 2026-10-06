import type { NextConfig } from 'next';
import createNextIntlPlugin from 'next-intl/plugin';

const withNextIntl = createNextIntlPlugin();

const nextConfig: NextConfig = {
  // ===================================================
  // FULL CLIENT-SIDE RENDERING (CSR) - SPA MODE
  // ===================================================
  // This Next.js app is configured as a Single Page Application (SPA)
  // with full client-side rendering. No server-side features are used.

  // Export as static SPA - generates static HTML/CSS/JS files
  // All pages are pre-rendered at build time into the 'out/' directory
  // No Node.js server required - can be deployed to any static hosting
  // Note: Disabled in dev for better DX, enable for production builds
  output: 'standalone',

  // Disable Next.js image optimization (requires server)
  // Images will be served as-is without optimization
  images: {
    unoptimized: true,
  },

  // Auto-memoization (replaces most hand-written useMemo/useCallback)
  reactCompiler: true,
};

export default withNextIntl(nextConfig);
