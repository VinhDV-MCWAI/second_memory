import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['src/test/setup.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text-summary', 'html'],
      // Gate the shared layer and feature hooks/services; components and pages are covered later.
      include: ['src/shared/**', 'src/features/*/hooks/**', 'src/features/*/services/**'],
      exclude: ['**/*.test.*', '**/*.d.ts', 'src/shared/types/**'],
      thresholds: { statements: 80, lines: 80, functions: 75, branches: 65 },
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
});
