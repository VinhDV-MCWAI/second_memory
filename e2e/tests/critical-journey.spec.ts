import { expect, test, type Page } from '@playwright/test';
import { E2E_PASSWORD, E2E_USER, PUBLIC_URL } from '../env';

// The critical journey of the Skill Ledger (RFC-002 §7, P3-15):
// login → create skill → add evidence → find it by search → see it on the public page,
// while a private skill stays hidden. Names carry a run id so runs never collide, and the
// `E2E ` prefix lets e2e/run.sh delete everything this spec created.
const runId = Date.now().toString(36);
const publicSkill = `E2E Skill ${runId}`;
const privateSkill = `E2E Private ${runId}`;
const evidenceTitle = `E2E Evidence ${runId}`;
// laravel-api SkillRepository::uniqueSlug = Str::slug(name) for a new name
const slugOf = (name: string) => name.toLowerCase().replaceAll(' ', '-');

// Ledger dates may not be in the future; yesterday is safe whatever the server's time zone.
const yesterday = () => new Date(Date.now() - 24 * 3600 * 1000).toISOString().slice(0, 10);

const HTTP_NOT_FOUND = 404;

async function createSkill(page: Page, name: string, isPublic: boolean) {
  await page.goto('/admin/skills');
  await page.getByRole('button', { name: 'Create Skill' }).click();
  const dialog = page.getByRole('dialog');
  await dialog.getByLabel('Name').fill(name);
  await dialog.getByLabel('Category').fill('E2E');
  if (isPublic) await dialog.getByLabel('Show on the public page').check();
  await dialog.getByRole('button', { name: 'Create', exact: true }).click();
  await expect(dialog).toBeHidden();
  await expect(page.getByRole('cell', { name, exact: true })).toBeVisible();
}

test('owner records a skill with evidence and it shows on the public page', async ({ page }) => {
  expect(E2E_PASSWORD, 'E2E_PASSWORD is set by e2e/run.sh').not.toBe('');

  await test.step('log in', async () => {
    await page.goto('/login');
    await page.getByLabel('Username').fill(E2E_USER);
    await page.getByLabel('Password').fill(E2E_PASSWORD);
    await page.getByRole('button', { name: 'Sign In' }).click();
    await expect(page).toHaveURL(/\/admin$/);
  });

  await test.step('create a public and a private skill', async () => {
    await createSkill(page, publicSkill, true);
    await createSkill(page, privateSkill, false);
  });

  await test.step('add public evidence to the public skill', async () => {
    await page.goto('/admin/evidence');
    await page.getByRole('button', { name: 'Create Evidence' }).click();
    const dialog = page.getByRole('dialog');
    await dialog.getByLabel('Title').fill(evidenceTitle);
    await dialog.getByLabel('Link (http or https)').fill(`https://example.com/e2e/${runId}`);
    await dialog.getByLabel('Date').fill(yesterday());
    await dialog.getByLabel('Summary').fill('Recorded by the end-to-end test.');
    await dialog.getByLabel(publicSkill, { exact: true }).check();
    await dialog.getByLabel('Show on the public page').check();
    await dialog.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(dialog).toBeHidden();
    await expect(page.getByRole('cell', { name: evidenceTitle, exact: true })).toBeVisible();
  });

  await test.step('find the evidence with the header search', async () => {
    const search = page.getByRole('search').getByRole('searchbox');
    await search.fill(evidenceTitle);
    await search.press('Enter');
    await expect(page).toHaveURL(/\/admin\/search\?q=/);
    await expect(page.getByText(evidenceTitle, { exact: true })).toBeVisible();
  });

  await test.step('see the skill and its evidence on the public page', async () => {
    await page.goto(`${PUBLIC_URL}/skills/${slugOf(publicSkill)}`);
    await expect(page.getByRole('heading', { level: 1, name: publicSkill })).toBeVisible();
    await expect(page.getByRole('link', { name: evidenceTitle })).toBeVisible();
  });

  await test.step('the private skill is not public', async () => {
    const response = await page.goto(`${PUBLIC_URL}/skills/${slugOf(privateSkill)}`);
    expect(response?.status()).toBe(HTTP_NOT_FOUND);
    await page.goto(`${PUBLIC_URL}/skills`);
    await expect(page.getByText(privateSkill)).toHaveCount(0);
  });
});
