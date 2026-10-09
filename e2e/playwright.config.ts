import { defineConfig, devices } from '@playwright/test';
import { ADMIN_UPSTREAM, ADMIN_URL, PUBLIC_UPSTREAM, PUBLIC_URL } from './env';

// The browser runs in a container on the Compose network, but the pages must keep the origins
// the stack is configured for (Sanctum stateful domain, CSRF Referer, NEXT_PUBLIC_API_URL), so
// Chromium resolves the host URLs to the services instead of rewriting them.
const hostRules = [`MAP ${new URL(ADMIN_URL).host} ${ADMIN_UPSTREAM}`];
if (!PUBLIC_URL.startsWith(ADMIN_URL)) {
  hostRules.push(`MAP ${new URL(PUBLIC_URL).host} ${PUBLIC_UPSTREAM}`);
}

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
    launchOptions: { args: [`--host-resolver-rules=${hostRules.join(',')}`] },
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
