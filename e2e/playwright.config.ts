import { defineConfig, devices } from '@playwright/test';
import { ADMIN_UPSTREAM, ADMIN_URL } from './env';

// The browser runs in a container on the Compose network, but the pages must keep the origins
// the stack is configured for (Sanctum stateful domain, CSRF Referer, NEXT_PUBLIC_API_URL), so
// Chromium resolves localhost:81 to nginx instead of rewriting the URLs. The public pages are
// behind the same nginx (/skills).
const hostRule = `MAP ${new URL(ADMIN_URL).host} ${ADMIN_UPSTREAM}`;

export default defineConfig({
  testDir: './tests',
  // One journey, run once: it creates data the next step depends on.
  workers: 1,
  retries: 0,
  // The dev servers compile a page on its first visit.
  timeout: 180_000,
  expect: { timeout: 20_000 },
  // e2e/run.sh mounts artifacts/ to keep the report and the traces of a failed run
  outputDir: 'artifacts/test-results',
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'artifacts/report' }]],
  use: {
    baseURL: ADMIN_URL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    navigationTimeout: 60_000,
    launchOptions: { args: [`--host-resolver-rules=${hostRule}`] },
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
