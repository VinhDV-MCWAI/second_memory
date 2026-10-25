import { expect, test, type Page } from '@playwright/test';
import { E2E_PASSWORD, E2E_USER, PUBLIC_URL } from '../env';

// End-user accounts of the public site (ADR-0020, REQ-003): a visitor clicks ☆ on a public article,
// creates an account, comes back to the article, keeps it as a favourite and to read later, sees
// both in My lists; the owner locks the account and the visitor is signed out. e2e/run.sh removes
// the `E2E …` article and every `e2e-…` end user afterwards.
const suffix = Date.now().toString(36);
const article = `E2E Shared ${suffix}`;
const slug = article.toLowerCase().replaceAll(' ', '-');
const username = `e2e-${suffix}`;
const password = 'e2e-visitor-1';

async function signInAsOwner(page: Page) {
  await page.goto('/admin/login');
  await page.getByLabel('Username').fill(E2E_USER);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);
}

test('a visitor signs up, saves an article, and is signed out once locked', async ({
  page,
  browser,
}) => {
  await test.step('the owner publishes an article', async () => {
    await signInAsOwner(page);
    await page.goto('/admin/articles');
    await page.getByRole('button', { name: 'Create Article' }).click();
    const dialog = page.getByRole('dialog');
    await dialog.getByLabel('Title').fill(article);
    await dialog.getByLabel('Content (Markdown)').fill('Worth reading twice.');
    await dialog.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(dialog).toBeHidden();
  });

  const context = await browser.newContext();
  const visitor = await context.newPage();
  const favorite = visitor.getByRole('button', { name: `Add ${article} to favorites` });
  const readLater = visitor.getByRole('button', { name: `Save ${article} to read later` });

  await test.step('a guest who clicks ☆ is asked to sign in, and registers', async () => {
    await visitor.goto(`${PUBLIC_URL}/articles/${slug}`);
    await favorite.click();
    await expect(visitor).toHaveURL(/\/account\/login\?next=/);
    await visitor.getByRole('link', { name: 'Create one' }).click();
    await visitor.getByLabel('Username (3–30 letters, digits, . _ -)').fill(username);
    await visitor.getByLabel('Display name').fill(`E2E visitor ${suffix}`);
    // Each field is checked once left, and the button stays off until every field passes
    const create = visitor.getByRole('button', { name: 'Create account' });
    const newPassword = visitor.getByLabel('Password (at least 8 characters)');
    const again = visitor.getByLabel('Password again');
    await newPassword.fill('short');
    await again.fill('other');
    await again.blur();
    await newPassword.focus();
    await newPassword.blur();
    await expect(newPassword).toHaveAccessibleDescription('At least 8 characters');
    await expect(again).toHaveAccessibleDescription('Does not match');
    await expect(create).toBeDisabled();
    await newPassword.fill(password);
    await again.fill(password);
    await expect(again).toHaveAccessibleDescription('');
    await create.click();
    // Back on the article it came from
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/articles/${slug}`);
    await expect(visitor.getByRole('link', { name: `E2E visitor ${suffix} · My lists` })).toBeVisible();
  });

  await test.step('☆ and 🔖 keep the article in My lists', async () => {
    await favorite.click();
    await expect(favorite).toHaveAttribute('aria-pressed', 'true');
    await readLater.click();
    await expect(readLater).toHaveAttribute('aria-pressed', 'true');
    await visitor.reload();
    await expect(favorite).toHaveAttribute('aria-pressed', 'true');

    await visitor.goto(`${PUBLIC_URL}/me`);
    for (const list of ['Favorites', 'Read later']) {
      const section = visitor.locator('section').filter({ has: visitor.getByRole('heading', { name: list }) });
      await expect(section.getByRole('link', { name: article })).toBeVisible();
    }
  });

  await test.step('the owner locks the account; the visitor is signed out', async () => {
    await page.goto('/admin/end-users');
    const row = page.getByRole('row').filter({ hasText: username });
    await expect(row).toBeVisible();
    await row.getByRole('button', { name: 'Edit' }).click();
    const dialog = page.getByRole('dialog');
    await dialog.getByLabel('Can sign in (untick to lock: signed out at once)').uncheck();
    await dialog.getByRole('button', { name: 'Update', exact: true }).click();
    await expect(dialog).toBeHidden();

    await visitor.goto(`${PUBLIC_URL}/me`);
    await expect(visitor).toHaveURL(/\/account\/login/);
  });

  await context.close();
});
