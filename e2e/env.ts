// Shared by the config and the specs.

// e2e/run.sh sets both for the target environment (dev by default, prod-like for the demo)
export const ADMIN_URL = process.env.E2E_ADMIN_URL ?? 'http://localhost:81';
export const ADMIN_UPSTREAM = process.env.E2E_ADMIN_UPSTREAM ?? 'ml-nginx:8080';
// The public pages go through the same nginx as the admin app (/skills → public-site, ADR-0019).
export const PUBLIC_URL = ADMIN_URL;

/** Throwaway owner account that e2e/run.sh creates before the run (fresh password each time). */
export const E2E_USER = process.env.E2E_USER ?? 'e2e_owner';
export const E2E_PASSWORD = process.env.E2E_PASSWORD ?? '';

/** Admins without the owner role, in their own department each (e2e/run.sh); same password. */
export const [E2E_MEMBER, E2E_OUTSIDER] = (
  process.env.E2E_HELPERS ?? 'e2e_member,e2e_outsider'
).split(',');
