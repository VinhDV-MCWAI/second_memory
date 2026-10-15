import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['src/test/setup.ts'],
    // Form tests type through user-event in jsdom; under load (make cd runs CI next to the dev stack)
    // one took over the 5 s default, so a slow machine no longer fails a correct test
    testTimeout: 15_000,
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
