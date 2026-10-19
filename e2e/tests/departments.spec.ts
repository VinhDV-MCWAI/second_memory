import { expect, test, type Page } from '@playwright/test';
import { E2E_MEMBER, E2E_OUTSIDER, E2E_PASSWORD, E2E_USER } from '../env';

// Departments and folder locks (ADR-0017): the owner adds a department on its screen, then locks a
// folder of Files to the member's department. The member still sees it, an admin of another
// department does not. e2e/run.sh prepares both admins and removes every `E2E …` record afterwards.
const suffix = Date.now().toString(36);
const department = `E2E department ${suffix}`;
const locked = `E2E locked ${suffix}`;
const open = `E2E open ${suffix}`;

async function signIn(page: Page, user: string) {
  await page.goto('/admin/login');
  await page.getByLabel('Username').fill(user);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);
}

test('owner locks a folder to a department; only its members see it', async ({ page, browser }) => {
  await signIn(page, E2E_USER);

  await page.goto('/admin/departments');
  await page.getByRole('button', { name: 'Create Department' }).click();
  const form = page.getByRole('dialog');
  await form.getByLabel('Code').fill(`E2E-${suffix}`);
  await form.getByLabel('Name').fill(department);
  await form.getByRole('button', { name: 'Create', exact: true }).click();
  await expect(form).toBeHidden();
  await expect(page.getByRole('row').filter({ hasText: department })).toBeVisible();

  await page.goto('/admin/files');
  for (const name of [locked, open]) {
    await page.getByRole('button', { name: 'New folder' }).click();
    await page.getByRole('dialog').getByLabel('Folder name').fill(name);
    await page.getByRole('dialog').getByRole('button', { name: 'Create' }).click();
    await expect(page.getByRole('button', { name, exact: true })).toBeVisible();
  }

  const row = page.getByRole('row').filter({ hasText: locked });
  await row.getByRole('button', { name: 'Access' }).click();
  const access = page.getByRole('dialog');
  await access.getByRole('checkbox', { name: `E2E ${E2E_MEMBER}` }).check();
  await access.getByRole('button', { name: 'Save' }).click();
  await expect(access).toBeHidden();
  await expect(row.getByRole('img', { name: 'Locked to departments' })).toBeVisible();

  for (const [user, seesLocked] of [
    [E2E_OUTSIDER, false],
    [E2E_MEMBER, true],
  ] as const) {
    const context = await browser.newContext();
    const other = await context.newPage();
    await signIn(other, user);
    await other.goto('/admin/files');
    await expect(other.getByRole('button', { name: open, exact: true })).toBeVisible();
    await expect(other.getByRole('row').filter({ hasText: locked })).toHaveCount(seesLocked ? 1 : 0);
    await context.close();
  }
});
