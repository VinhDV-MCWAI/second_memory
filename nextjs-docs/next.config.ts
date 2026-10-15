import type { NextConfig } from 'next';

const nextConfig: NextConfig = {
  assetPrefix: '/docs',
  // Self-contained server for the production image (docker/nextjs/Dockerfile copies .next/standalone)
  output: 'standalone',
  // Auto-memoization (replaces most hand-written useMemo/useCallback)
  reactCompiler: true,
};

export default nextConfig;
