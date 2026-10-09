// Shared by the config and the specs.

export const ADMIN_URL = 'http://localhost:81';
export const ADMIN_UPSTREAM = 'ml-nginx:8080';
// The public pages go through the same nginx as the admin app (/skills → nextjs-docs, OPS-04).
export const PUBLIC_URL = ADMIN_URL;

/** Throwaway owner account that e2e/run.sh creates before the run (fresh password each time). */
export const E2E_USER = process.env.E2E_USER ?? 'e2e_owner';
export const E2E_PASSWORD = process.env.E2E_PASSWORD ?? '';
