import { expect, test, type Page } from '@playwright/test';
import { E2E_PASSWORD, E2E_USER, PUBLIC_URL } from '../env';

// The critical journey of the experience docs site (REQ-005, ADR-0022):
// login → group and category → a book with a chapter that links and embeds it → find it with the
// admin search → read it on the public site like a documentation page, while a private article
// stays hidden. Names carry a run id so runs never collide, and the `E2E ` prefix lets
// e2e/run.sh delete everything this spec created.
const runId = Date.now().toString(36);
const group = `E2E Group ${runId}`;
const category = `E2E Topic ${runId}`;
const book = `E2E Book ${runId}`;
const chapter = `E2E Chapter ${runId}`;
const privateArticle = `E2E Private ${runId}`;
const overview = `Overview text of run ${runId}`;
const later = `Later ${runId}`;
// Enough text after a heading for it to scroll to the top of the window
const filler = Array.from({ length: 40 }, (_, i) => `Paragraph ${i + 1} of the book.`).join('\n\n');
// laravel-api uniqueSlug = Str::slug(name) for a new name
const slugOf = (name: string) => name.toLowerCase().replaceAll(' ', '-');

const HTTP_NOT_FOUND = 404;
// The public pages keep answers for up to a minute
const CACHED = { timeout: 90_000, intervals: [5_000] };

async function createCategory(page: Page, name: string, inGroup?: string) {
  await page.goto('/admin/content-categories');
  await page.getByRole('button', { name: 'Create Category' }).click();
  // The tree pickers open popovers, which are dialogs too
  const dialog = page.getByRole('dialog', { name: 'Create Category' });
  await dialog.getByLabel('Name').fill(name);
  if (inGroup) {
    await dialog.getByRole('combobox', { name: 'Parent category' }).click();
    await page.getByRole('option', { name: inGroup, exact: true }).click();
  }
  await dialog.getByRole('button', { name: 'Create', exact: true }).click();
  await expect(dialog).toBeHidden();
}

async function createArticle(
  page: Page,
  title: string,
  body: string,
  options: { category?: string; parent?: string; isPublic?: boolean } = {},
) {
  await page.goto('/admin/articles');
  await page.getByRole('button', { name: 'Create Article' }).click();
  const dialog = page.getByRole('dialog', { name: 'Create Article' });
  await dialog.getByLabel('Title').fill(title);
  if (options.category) {
    await dialog.getByRole('combobox', { name: 'Categories' }).click();
    await page.getByRole('textbox', { name: 'Search categories' }).fill(options.category);
    // While searching, a row reads as its whole path (group › category)
    await page.getByLabel(options.category).check();
    await page.keyboard.press('Escape');
  }
  if (options.parent) {
    await dialog.getByRole('combobox', { name: 'Parent article' }).click();
    await page.getByRole('option', { name: options.parent }).click();
  }
  await dialog.getByLabel('Content (Markdown)').fill(body);
  if (options.isPublic === false) await dialog.getByLabel('Show on the public page').uncheck();
  await dialog.getByRole('button', { name: 'Create', exact: true }).click();
  await expect(dialog).toBeHidden();
  await expect(page.getByRole('row').filter({ hasText: title })).toBeVisible();
}

test('owner writes a book and visitors read it like a documentation site', async ({
  page,
  browser,
}) => {
  expect(E2E_PASSWORD, 'E2E_PASSWORD is set by e2e/run.sh').not.toBe('');

  await test.step('log in', async () => {
    await page.goto('/admin/login');
    await page.getByLabel('Username').fill(E2E_USER);
    await page.getByLabel('Password').fill(E2E_PASSWORD);
    await page.getByRole('button', { name: 'Sign In' }).click();
    await expect(page).toHaveURL(/\/admin$/);
  });

  await test.step('a group with a category in it', async () => {
    await createCategory(page, group);
    await createCategory(page, category, group);
  });

  await test.step('a book, a chapter that links and embeds it, and a private article', async () => {
    await createArticle(page, book, `## Overview\n\n${overview}\n\n## ${later}\n\nNot embedded.\n\n${filler}`, {
      category,
    });
    await createArticle(
      page,
      chapter,
      `Back to [[${slugOf(book)}|the book]].\n\n![[${slugOf(book)}#Overview]]\n\n## Steps\n\n\`\`\`php\necho 1;\n\`\`\``,
      { category, parent: book },
    );
    await createArticle(page, privateArticle, 'Only for the owner.', { category, isPublic: false });
  });

  await test.step('find the book with the header search', async () => {
    const search = page.getByRole('search').getByRole('searchbox');
    await search.fill(book);
    await search.press('Enter');
    await expect(page).toHaveURL(/\/admin\/search\?q=/);
    await expect(page.getByText(book, { exact: true }).first()).toBeVisible();
  });

  const visitor = await (await browser.newContext()).newPage();

  await test.step('home → group → category → book, without signing in', async () => {
    await expect(async () => {
      await visitor.goto(`${PUBLIC_URL}/`);
      await expect(visitor.getByRole('link', { name: group })).toBeVisible({ timeout: 1_000 });
    }).toPass(CACHED);
    await visitor.getByRole('link', { name: category }).click();
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/categories/${slugOf(category)}`);
    await expect(visitor.getByRole('heading', { level: 1, name: category })).toBeVisible();
    await expect(visitor.getByText(privateArticle)).toHaveCount(0);
    await visitor.getByRole('link', { name: book }).first().click();
    await expect(visitor.getByRole('heading', { level: 1, name: book })).toBeVisible();
  });

  await test.step('the chapter page: link, embedded section, contents, book menu', async () => {
    await visitor.goto(`${PUBLIC_URL}/articles/${slugOf(chapter)}`);
    await expect(visitor.getByRole('heading', { level: 1, name: chapter })).toBeVisible();
    await expect(visitor.getByRole('link', { name: 'the book' })).toHaveAttribute(
      'href',
      `/articles/${slugOf(book)}`,
    );
    // Only the Overview section is embedded, not the rest of the book
    await expect(visitor.getByText(overview)).toBeVisible();
    await expect(visitor.getByText('Not embedded.')).toHaveCount(0);
    const contents = visitor.locator('nav').filter({ hasText: 'On this page' });
    await expect(contents.getByRole('link', { name: 'Steps' })).toBeVisible();
    await expect(visitor.getByRole('link', { name: book }).first()).toBeVisible();
  });

  await test.step('home: the Newest tab lists the chapter, See more opens the reading page (REQ-006)', async () => {
    await expect(async () => {
      await visitor.goto(`${PUBLIC_URL}/?articles=newest`);
      const newest = visitor.locator('#articles').getByRole('listitem').first();
      await expect(newest.getByRole('link', { name: chapter })).toBeVisible({ timeout: 1_000 });
    }).toPass(CACHED);
    await visitor.locator('#articles').getByRole('link', { name: 'See more →' }).click();
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/articles/${slugOf(chapter)}`);
  });

  await test.step('the reading page swaps articles without a reload, Back included (ADR-0024)', async () => {
    await visitor.goto(`${PUBLIC_URL}/articles/${slugOf(book)}?in=${slugOf(category)}`);
    // Gone if the browser loads a whole new page
    await visitor.evaluate(() => Object.assign(window, { e2eSamePage: true }));
    const samePage = () => visitor.evaluate(() => 'e2eSamePage' in window);
    await visitor
      .getByRole('navigation', { name: `Articles in ${category}` })
      .getByRole('link', { name: chapter })
      .click();
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/articles/${slugOf(chapter)}?in=${slugOf(category)}`);
    await expect(visitor.getByRole('heading', { level: 1, name: chapter })).toBeVisible();
    expect(await samePage()).toBe(true);
    await visitor.goBack();
    await expect(visitor.getByRole('heading', { level: 1, name: book })).toBeVisible();
    expect(await samePage()).toBe(true);
  });

  await test.step('"On this page" lights the section being read', async () => {
    const contents = visitor.getByRole('navigation', { name: 'On this page' });
    await contents.getByRole('link', { name: later }).click();
    await expect(visitor.getByRole('heading', { level: 2, name: later })).toBeInViewport();
    await expect(contents.getByRole('link', { name: later })).toHaveAttribute('aria-current', 'true');
    await expect(contents.getByRole('link', { name: 'Overview' })).not.toHaveAttribute('aria-current', 'true');
  });

  await test.step('the public search finds the book; the private article is not public', async () => {
    await visitor.goto(`${PUBLIC_URL}/search?q=${encodeURIComponent(book)}`);
    await expect(visitor.getByRole('link', { name: book }).first()).toBeVisible();
    await visitor.goto(`${PUBLIC_URL}/search?q=${encodeURIComponent(privateArticle)}`);
    await expect(visitor.getByRole('link', { name: privateArticle })).toHaveCount(0);
    const response = await visitor.goto(`${PUBLIC_URL}/articles/${slugOf(privateArticle)}`);
    expect(response?.status()).toBe(HTTP_NOT_FOUND);
  });

  await test.step('search results say what they are and open the right section (REQ-006)', async () => {
    const results = visitor.getByRole('region', { name: 'Results' });
    await visitor.goto(`${PUBLIC_URL}/search?q=${encodeURIComponent(overview)}`);
    const content = results.getByRole('listitem').filter({ hasText: 'Content' }).filter({ hasText: book });
    await expect(content.locator('mark').first()).toBeVisible();

    // The header's box shows results under it once typing pauses, without Enter
    await visitor.goto(`${PUBLIC_URL}/`);
    await visitor.getByRole('search').getByRole('searchbox').fill(later);
    const section = visitor.locator('#header-search-panel').getByRole('listitem').filter({ hasText: 'Section' });
    await expect(section.locator('mark')).toHaveText(later.split(' '));
    await section.getByRole('link', { name: later }).click();
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/articles/${slugOf(book)}#${slugOf(later)}`);
    await expect(visitor.getByRole('heading', { level: 2, name: later })).toBeInViewport();
  });

  await test.step('old links land on the right pages (ADR-0021, ADR-0022)', async () => {
    await visitor.goto(`${PUBLIC_URL}/docs/some/old/page`);
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/`);
    await expect(visitor.getByRole('heading', { level: 1, name: 'Second Memory' })).toBeVisible();
    await visitor.goto(`${PUBLIC_URL}/guides/${slugOf(category)}/${slugOf(book)}`);
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/articles/${slugOf(book)}`);
    await visitor.goto(`${PUBLIC_URL}/skills`);
    await expect(visitor).toHaveURL(`${PUBLIC_URL}/`);
    await visitor.goto(`${PUBLIC_URL}/skills/me`);
    await expect(visitor).toHaveURL(/\/(me|account\/login\?next=%2Fme)$/);
    await visitor.goto(`${PUBLIC_URL}/login`);
    await expect(visitor).toHaveURL(/\/admin(\/login)?/);
  });
});
