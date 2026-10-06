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

  // Disable server-side features
  experimental: {
    // Add experimental features as needed
  },

  // Development mode optimizations (only used during 'next dev')
  // These settings help with hot-reload in Docker environment
  webpack: (config, { dev, isServer }) => {
    if (dev && !isServer) {
      // Enable file watching with polling for Docker environments
      // Required because Docker's file system events don't propagate to containers
      if (process.env.WATCHPACK_POLLING === 'true') {
        config.watchOptions = {
          poll: 1000, // Check for changes every 1 second
          aggregateTimeout: 300, // Delay rebuild after detecting changes
          ignored: /node_modules/, // Don't watch node_modules
        };
      }
    }

    return config;
  },
};

export default withNextIntl(nextConfig);
