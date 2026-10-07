import type { NextConfig } from 'next';

const nextConfig: NextConfig = {
  // Full client-side rendering - all pages use 'use client'
  // Note: Static export disabled to allow dynamic routing without generateStaticParams
  // For production static export, you would need to add generateStaticParams to dynamic routes

  assetPrefix: '/docs',
  images: {
    unoptimized: true,
  },
  // Auto-memoization (replaces most hand-written useMemo/useCallback)
  reactCompiler: true,
};

export default nextConfig;
