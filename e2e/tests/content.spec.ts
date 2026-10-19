import { expect, test } from '@playwright/test';
import { E2E_PASSWORD, E2E_USER, PUBLIC_URL } from '../env';

// Home page categories and articles (ADR-0021, REQ-004): the owner adds a category and an article
// in the admin app; a visitor finds them from the home page and reads the Markdown as HTML; a
// hidden category disappears. e2e/run.sh removes every `E2E …` category and its articles.
const suffix = Date.now().toString(36);
const category = `E2E Guide ${suffix}`;
const categorySlug = category.toLowerCase().replaceAll(' ', '-');
const article = `E2E Article ${suffix}`;
const HTTP_NOT_FOUND = 404;

test('owner publishes a category and an article; visitors read them from the home page', async ({
  page,
  browser,
}) => {
  await page.goto('/admin/login');
  await page.getByLabel('Username').fill(E2E_USER);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);

  await test.step('the owner adds a category and an article', async () => {
    await page.goto('/admin/content-categories');
    await page.getByRole('button', { name: 'Create Category' }).click();
    const form = page.getByRole('dialog');
    await form.getByLabel('Name').fill(category);
    await form.getByLabel('Description').fill('Written by the browser test');
    await form.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(form).toBeHidden();

    await page.goto('/admin/articles');
    await page.getByRole('button', { name: 'Create Article' }).click();
    await form.getByRole('combobox').click();
    await page.getByRole('option', { name: category }).click();
    await form.getByLabel('Title').fill(article);
    await form.getByLabel('Content (Markdown)').fill('## First step\n\n| A | B |\n|---|---|\n| 1 | 2 |');
    await form.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(form).toBeHidden();
    await expect(page.getByRole('row').filter({ hasText: article })).toBeVisible();
  });

  const visitor = await (await browser.newContext()).newPage();

  await test.step('a visitor goes home → category → article, without signing in', async () => {
    await visitor.goto(`${PUBLIC_URL}/`);
    await expect(visitor.getByRole('link', { name: /^Skills/ })).toBeVisible();
    await visitor.getByRole('link', { name: new RegExp(category) }).click();
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/guides/${categorySlug}`);
    await visitor.getByRole('link', { name: article }).click();
    await expect(visitor.getByRole('heading', { level: 1, name: article })).toBeVisible();
    await expect(visitor.getByRole('heading', { level: 2, name: 'First step' })).toBeVisible();
    await expect(visitor.getByRole('table')).toBeVisible();
  });

  await test.step('a hidden category is gone for visitors', async () => {
    await page.goto('/admin/content-categories');
    await page.getByRole('row').filter({ hasText: category }).getByRole('button', { name: 'Edit' }).click();
    const form = page.getByRole('dialog');
    await form.getByLabel('Public').uncheck();
    await form.getByRole('button', { name: 'Update' }).click();
    await expect(form).toBeHidden();

    // The public pages keep answers for up to a minute
    await expect
      .poll(async () => (await visitor.goto(`${PUBLIC_URL}/guides/${categorySlug}`))?.status(), {
        timeout: 90_000,
        intervals: [5_000],
      })
      .toBe(HTTP_NOT_FOUND);
  });
});
