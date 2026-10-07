import type { NextConfig } from 'next';

const nextConfig: NextConfig = {
  assetPrefix: '/docs',
  // Auto-memoization (replaces most hand-written useMemo/useCallback)
  reactCompiler: true,
};

export default nextConfig;
