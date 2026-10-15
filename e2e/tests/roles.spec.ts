import { expect, test } from '@playwright/test';
import { E2E_PASSWORD, E2E_USER } from '../env';

// Dynamic permissions (ADR-0013): the owner creates a role on the roles screen by ticking
// permissions in the matrix. The `E2E ` prefix lets e2e/run.sh delete the role afterwards.
const roleName = `E2E Tag reader ${Date.now().toString(36)}`;

test('owner creates a role from the permission matrix', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Username').fill(E2E_USER);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);

  await page.goto('/admin/roles');
  await expect(page.getByRole('cell', { name: 'owner', exact: true })).toBeVisible();

  await page.getByRole('button', { name: 'Create Role' }).click();
  const dialog = page.getByRole('dialog');
  await dialog.getByLabel('Name').fill(roleName);
  await dialog.getByRole('checkbox', { name: 'tag.list' }).check();
  await dialog.getByRole('checkbox', { name: 'skill.list' }).check();
  await dialog.getByRole('button', { name: 'Create', exact: true }).click();
  await expect(dialog).toBeHidden();

  const row = page.getByRole('row').filter({ hasText: roleName });
  await expect(row).toBeVisible();
  await expect(row.getByRole('cell', { name: '2', exact: true })).toBeVisible();
});
