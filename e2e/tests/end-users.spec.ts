import { expect, test, type Page } from '@playwright/test';
import { E2E_PASSWORD, E2E_USER, PUBLIC_URL } from '../env';

// End-user accounts of the public site (ADR-0020, REQ-003): a visitor clicks ☆ on a public skill,
// creates an account, comes back to the skill, keeps it as a favourite and to read later, sees both
// in My lists; the owner locks the account and the visitor is signed out. e2e/run.sh removes the
// `E2E …` skill and every `e2e-…@example.com` end user afterwards.
const suffix = Date.now().toString(36);
const skill = `E2E Shared ${suffix}`;
const slug = skill.toLowerCase().replaceAll(' ', '-');
const email = `e2e-${suffix}@example.com`;
const password = 'e2e-visitor-1';

async function signInAsOwner(page: Page) {
  await page.goto('/login');
  await page.getByLabel('Username').fill(E2E_USER);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);
}

test('a visitor signs up, saves a skill, and is signed out once locked', async ({ page, browser }) => {
  await test.step('the owner publishes a skill', async () => {
    await signInAsOwner(page);
    await page.goto('/admin/skills');
    await page.getByRole('button', { name: 'Create Skill' }).click();
    const dialog = page.getByRole('dialog');
    await dialog.getByLabel('Name').fill(skill);
    await dialog.getByLabel('Category').fill('E2E');
    await dialog.getByLabel('Show on the public page').check();
    await dialog.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(dialog).toBeHidden();
  });

  const context = await browser.newContext();
  const visitor = await context.newPage();
  const favorite = visitor.getByRole('button', { name: `Add ${skill} to favorites` });
  const readLater = visitor.getByRole('button', { name: `Save ${skill} to read later` });

  await test.step('a guest who clicks ☆ is asked to sign in, and registers', async () => {
    await visitor.goto(`${PUBLIC_URL}/skills/${slug}`);
    await favorite.click();
    await expect(visitor).toHaveURL(/\/skills\/account\/login\?next=/);
    await visitor.getByRole('link', { name: 'Create one' }).click();
    await visitor.getByLabel('Email').fill(email);
    await visitor.getByLabel('Display name').fill(`E2E visitor ${suffix}`);
    await visitor.getByLabel('Password (at least 8 characters)').fill(password);
    await visitor.getByLabel('Password again').fill(password);
    await visitor.getByRole('button', { name: 'Create account' }).click();
    // Back on the skill it came from
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/skills/${slug}`);
    await expect(visitor.getByRole('link', { name: `E2E visitor ${suffix} · My lists` })).toBeVisible();
  });

  await test.step('☆ and 🔖 keep the skill in My lists', async () => {
    await favorite.click();
    await expect(favorite).toHaveAttribute('aria-pressed', 'true');
    await readLater.click();
    await expect(readLater).toHaveAttribute('aria-pressed', 'true');
    await visitor.reload();
    await expect(favorite).toHaveAttribute('aria-pressed', 'true');

    await visitor.goto(`${PUBLIC_URL}/skills/me`);
    for (const list of ['Favorites', 'Read later']) {
      const section = visitor.locator('section').filter({ has: visitor.getByRole('heading', { name: list }) });
      await expect(section.getByRole('link', { name: skill })).toBeVisible();
    }
  });

  await test.step('the owner locks the account; the visitor is signed out', async () => {
    await page.goto('/admin/end-users');
    const row = page.getByRole('row').filter({ hasText: email });
    await expect(row).toBeVisible();
    await row.getByRole('button', { name: 'Edit' }).click();
    const dialog = page.getByRole('dialog');
    await dialog.getByLabel('Can sign in (untick to lock: signed out at once)').uncheck();
    await dialog.getByRole('button', { name: 'Update', exact: true }).click();
    await expect(dialog).toBeHidden();

    await visitor.goto(`${PUBLIC_URL}/skills/me`);
    await expect(visitor).toHaveURL(/\/skills\/account\/login/);
  });

  await context.close();
});
