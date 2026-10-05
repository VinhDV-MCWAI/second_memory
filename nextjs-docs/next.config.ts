import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Full client-side rendering - all pages use 'use client'
  // Note: Static export disabled to allow dynamic routing without generateStaticParams
  // For production static export, you would need to add generateStaticParams to dynamic routes
  
  assetPrefix: "/docs",
  images: {
    unoptimized: true,
  },
  // Silence turbopack/webpack warning
  turbopack: {},
  webpack: (config, { dev, isServer }) => {
    if (dev && !isServer) {
      if (process.env.WATCHPACK_POLLING === 'true') {
        config.watchOptions = {
          poll: 1000,
          aggregateTimeout: 300,
          ignored: /node_modules/,
        };
      }
    }
    return config;
  },
};

export default nextConfig;
