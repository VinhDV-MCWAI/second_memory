// Shared by the config and the specs.

export const ADMIN_URL = 'http://localhost:81';
export const ADMIN_UPSTREAM = 'ml-nginx:8080';
// Until nginx routes /skills (OPS-04) the public pages are reached on the docs app's own port.
export const PUBLIC_URL = process.env.E2E_PUBLIC_URL ?? 'http://localhost:3002';
export const PUBLIC_UPSTREAM = process.env.E2E_PUBLIC_UPSTREAM ?? 'ml-nextjs-docs:3457';

/** Throwaway owner account that e2e/run.sh creates before the run (fresh password each time). */
export const E2E_USER = process.env.E2E_USER ?? 'e2e_owner';
export const E2E_PASSWORD = process.env.E2E_PASSWORD ?? '';
