import { expect, test } from '@playwright/test';
import { E2E_PASSWORD, E2E_USER, PUBLIC_URL } from '../env';

// Categories and articles (ADR-0021, ADR-0022, ADR-0023): the owner adds a category and an article
// in the admin app and drags a second category into the first in the tree; a visitor finds them
// from the home page and reads the Markdown as HTML; a hidden category disappears. e2e/run.sh
// removes every `E2E …` category and its articles.
const suffix = Date.now().toString(36);
const category = `E2E Guide ${suffix}`;
const categorySlug = category.toLowerCase().replaceAll(' ', '-');
const child = `E2E Child ${suffix}`;
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
    const form = page.getByRole('dialog', { name: 'Create Category' });
    await form.getByLabel('Name').fill(category);
    await form.getByLabel('Description').fill('Written by the browser test');
    await form.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(form).toBeHidden();

    await page.goto('/admin/articles');
    await page.getByRole('button', { name: 'Create Article' }).click();
    const articleForm = page.getByRole('dialog', { name: 'Create Article' });
    await articleForm.getByLabel('Title').fill(article);
    // The categories picker is a tree with a search, in a popover outside the dialog
    await articleForm.getByRole('combobox', { name: 'Categories' }).click();
    await page.getByRole('textbox', { name: 'Search categories' }).fill(category);
    await page.getByLabel(category, { exact: true }).check();
    await page.keyboard.press('Escape');
    await articleForm.getByLabel('Content (Markdown)').fill('## First step\n\n| A | B |\n|---|---|\n| 1 | 2 |');
    await articleForm.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(articleForm).toBeHidden();
    await expect(page.getByRole('row').filter({ hasText: article })).toBeVisible();
  });

  await test.step('a category dragged onto another goes inside it', async () => {
    await page.goto('/admin/content-categories');
    await page.getByRole('button', { name: 'Create Category' }).click();
    const form = page.getByRole('dialog', { name: 'Create Category' });
    await form.getByLabel('Name').fill(child);
    await form.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(form).toBeHidden();

    const target = page.getByRole('treeitem', { name: category, exact: true });
    const handle = page.getByRole('button', { name: `Drag ${child}` });
    // The middle of a row drops inside it; small steps so the drag starts and follows the pointer.
    // Hovering may scroll the page, so the target is measured after it
    await handle.hover();
    const box = await target.boundingBox();
    expect(box).not.toBeNull();
    if (!box) return;
    await page.mouse.down();
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2, { steps: 10 });
    await page.mouse.up();
    await expect(page.getByRole('treeitem', { name: child, exact: true })).toHaveAttribute('aria-level', '2');
  });

  const visitor = await (await browser.newContext()).newPage();

  await test.step('a visitor goes home → category → article, without signing in', async () => {
    // The home page keeps its answer for up to a minute
    await expect(async () => {
      await visitor.goto(`${PUBLIC_URL}/`);
      await expect(visitor.getByRole('link', { name: category })).toBeVisible({ timeout: 1_000 });
    }).toPass({ timeout: 90_000, intervals: [5_000] });
    await visitor.getByRole('link', { name: category }).click();
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/categories/${categorySlug}`);
    await visitor.getByRole('link', { name: article }).click();
    await expect(visitor.getByRole('heading', { level: 1, name: article })).toBeVisible();
    await expect(visitor.getByRole('heading', { level: 2, name: 'First step' })).toBeVisible();
    await expect(visitor.getByRole('table')).toBeVisible();
  });

  await test.step('a hidden category is gone for visitors', async () => {
    await page.goto('/admin/content-categories');
    await page.getByRole('textbox', { name: 'Search categories' }).fill(category);
    await page.getByRole('treeitem', { name: category, exact: true }).getByRole('button', { name: 'Edit' }).click();
    const form = page.getByRole('dialog', { name: 'Edit Category' });
    await form.getByLabel('Show on the public page').uncheck();
    await form.getByRole('button', { name: 'Update' }).click();
    await expect(form).toBeHidden();

    // The public pages keep answers for up to a minute
    await expect
      .poll(async () => (await visitor.goto(`${PUBLIC_URL}/categories/${categorySlug}`))?.status(), {
        timeout: 90_000,
        intervals: [5_000],
      })
      .toBe(HTTP_NOT_FOUND);
  });
});
