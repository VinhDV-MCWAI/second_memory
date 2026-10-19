import { mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { expect, test } from '@playwright/test';
import { E2E_PASSWORD, E2E_USER, PUBLIC_URL } from '../env';

// Files attached to evidence (LEDGER-01, LEDGER-02): upload and pick files in the evidence form,
// reorder them, save an evidence without a link, open a file from the public page, then delete one
// in Files and see it marked Deleted; the Evidence column of Files opens the evidence of a file
// (LEDGER-03). Everything is named `E2E …` (files sit at /E2E …) so
// e2e/run.sh removes it afterwards.
const runId = Date.now().toString(36);
const skill = `E2E Files skill ${runId}`;
const evidenceTitle = `E2E Files evidence ${runId}`;
const first = `E2E first ${runId}.txt`;
const second = `E2E second ${runId}.txt`;
// laravel-api SkillRepository::uniqueSlug = Str::slug(name) for a new name
const slug = skill.toLowerCase().replaceAll(' ', '-');
const yesterday = () => new Date(Date.now() - 24 * 3600 * 1000).toISOString().slice(0, 10);

test('owner attaches files to evidence and they show on the public page', async ({ page }) => {
  const dir = mkdtempSync(join(tmpdir(), 'e2e-evidence-files-'));
  writeFileSync(join(dir, first), 'first attachment');
  writeFileSync(join(dir, second), 'second attachment');

  await page.goto('/admin/login');
  await page.getByLabel('Username').fill(E2E_USER);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);

  await test.step('create a public skill', async () => {
    await page.goto('/admin/skills');
    await page.getByRole('button', { name: 'Create Skill' }).click();
    const dialog = page.getByRole('dialog');
    await dialog.getByLabel('Name').fill(skill);
    await dialog.getByLabel('Category').fill('E2E');
    await dialog.getByLabel('Show on the public page').check();
    await dialog.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(dialog).toBeHidden();
  });

  await test.step('upload two files in the picker, attach them and put the second first', async () => {
    await page.goto('/admin/evidence');
    await page.getByRole('button', { name: 'Create Evidence' }).click();
    const form = page.getByRole('dialog').first();
    await form.getByLabel('Title').fill(evidenceTitle);
    await form.getByLabel('Date').fill(yesterday());
    await form.getByLabel(skill, { exact: true }).check();
    await form.getByLabel('Show on the public page').check();

    await form.getByRole('button', { name: 'Attach files' }).click();
    const picker = page.getByRole('dialog', { name: 'Attach files' });
    await picker.getByTestId('picker-file-input').setInputFiles([join(dir, first), join(dir, second)]);
    // Ticking is possible once each upload is complete
    await expect(picker.getByLabel(first)).toBeEnabled();
    await expect(picker.getByLabel(second)).toBeEnabled();
    await picker.getByLabel(first).check();
    await picker.getByLabel(second).check();
    await picker.getByRole('button', { name: 'Attach (2)' }).click();
    await expect(picker).toBeHidden();

    const attached = form.getByRole('listitem').filter({ hasText: 'E2E ' });
    await expect(attached).toHaveText([new RegExp(first), new RegExp(second)]);
    await attached.filter({ hasText: second }).getByRole('button', { name: 'Move up' }).click();
    await expect(attached).toHaveText([new RegExp(second), new RegExp(first)]);

    // No link: an evidence of files only
    await form.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(form).toBeHidden();
    const row = page.getByRole('row').filter({ hasText: evidenceTitle });
    await expect(row.getByTitle('2 files attached')).toBeVisible();
  });

  await test.step('the public page lists the files in that order and opens them', async () => {
    await page.goto(`${PUBLIC_URL}/skills/${slug}`);
    // Files only: the title is plain text, not a link
    await expect(page.getByText(evidenceTitle, { exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: evidenceTitle })).toHaveCount(0);
    const links = page.getByRole('link', { name: /^E2E (first|second)/ });
    await expect(links).toHaveText([second, first]);
    // A text file opens in the browser through a fresh short-lived link
    await page.goto(new URL((await links.first().getAttribute('href')) ?? '', page.url()).href);
    await expect(page.locator('body')).toHaveText('second attachment');
  });

  await test.step('the Evidence column of Files opens the evidence using that file', async () => {
    await page.goto('/admin/files');
    await page.getByRole('link', { name: `Open the evidence using ${first}` }).click();
    await expect(page).toHaveURL(/\/admin\/evidence\?media_id=\d+/);
    await expect(page.getByText(`Evidence using the file ${first}`)).toBeVisible();
    await expect(page.getByRole('row').filter({ hasText: 'E2E ' })).toHaveCount(1);
    await expect(page.getByRole('row').filter({ hasText: evidenceTitle })).toBeVisible();
    await page.getByRole('button', { name: 'Show all evidence' }).click();
    await expect(page).toHaveURL(/\/admin\/evidence$/);
  });

  await test.step('a file deleted in Files stays attached, marked Deleted', async () => {
    await page.goto('/admin/files');
    await page.getByRole('row').filter({ hasText: second }).getByRole('button', { name: 'Delete' }).click();
    const confirm = page.getByRole('alertdialog');
    await expect(confirm).toContainText('1 attachment to evidence will show as Deleted');
    await confirm.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByRole('row').filter({ hasText: second })).toBeHidden();

    await page.goto('/admin/evidence');
    await page.getByRole('row').filter({ hasText: evidenceTitle }).getByRole('button', { name: 'Edit' }).click();
    const form = page.getByRole('dialog');
    await expect(form.getByRole('listitem').filter({ hasText: second })).toContainText('Deleted');
    await expect(form.getByRole('listitem').filter({ hasText: first })).not.toContainText('Deleted');
  });
});
