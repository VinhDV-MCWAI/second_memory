import { mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { expect, test } from '@playwright/test';
import { E2E_PASSWORD, E2E_USER } from '../env';

// File manager (ADR-0014): a folder, a small upload (one presigned PUT), a 120 MB upload (parts,
// background join, live notification), rename, download and delete, all through nginx like in
// production. The `E2E ` prefix lets e2e/run.sh remove what a failed run leaves behind.
const folder = `E2E files ${Date.now().toString(36)}`;
const MB = 1024 * 1024;

test('owner manages files: folder, small and large upload, rename, download, delete', async ({ page }) => {
  const dir = mkdtempSync(join(tmpdir(), 'e2e-files-'));
  const small = join(dir, 'note.txt');
  const large = join(dir, 'video.bin');
  writeFileSync(small, 'hello from e2e');
  writeFileSync(large, Buffer.alloc(120 * MB, 7));

  await page.goto('/login');
  await page.getByLabel('Username').fill(E2E_USER);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);

  await page.getByRole('link', { name: 'Files' }).click();
  await expect(page).toHaveURL(/\/admin\/files$/);

  await page.getByRole('button', { name: 'New folder' }).click();
  await page.getByRole('dialog').getByLabel('Folder name').fill(folder);
  await page.getByRole('dialog').getByRole('button', { name: 'Create' }).click();
  await page.getByRole('button', { name: folder }).click();
  await expect(page).toHaveURL(/folder=/);

  await page.getByTestId('media-file-input').setInputFiles([small, large]);
  await expect(page.getByRole('cell', { name: 'note.txt' })).toBeVisible();
  // The live notification of the background job that joined the parts (shown for a few seconds)
  await expect(page.getByText('video.bin is ready')).toBeVisible({ timeout: 60_000 });
  await expect(page.getByRole('listitem').filter({ hasText: 'video.bin' })).toContainText('Done');
  await expect(page.getByRole('cell', { name: 'video.bin' })).toBeVisible();
  await expect(page.getByRole('row').filter({ hasText: 'video.bin' })).toContainText('120 MB');

  const row = page.getByRole('row').filter({ hasText: 'note.txt' });
  await row.getByRole('button', { name: 'Rename' }).click();
  await page.getByRole('dialog').getByLabel('New name').fill('renamed.txt');
  await page.getByRole('dialog').getByRole('button', { name: 'Save' }).click();
  const renamed = page.getByRole('row').filter({ hasText: 'renamed.txt' });
  await expect(renamed).toBeVisible();

  const download = page.waitForEvent('download');
  await renamed.getByRole('button', { name: 'Download' }).click();
  expect((await download).suggestedFilename()).toBe('renamed.txt');

  await page.getByRole('button', { name: 'Files', exact: true }).first().click();
  await page.getByRole('row').filter({ hasText: folder }).getByRole('button', { name: 'Delete' }).click();
  await page.getByRole('alertdialog').getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByRole('button', { name: folder })).toBeHidden();
});

// Resuming a large upload (MEDIA-05): the connection drops after three parts, the page is reloaded,
// the same file is chosen again and only the missing parts go up.
test('owner resumes a large upload after the page is reloaded', async ({ page }) => {
  test.setTimeout(120_000);
  const dir = mkdtempSync(join(tmpdir(), 'e2e-resume-'));
  const large = join(dir, 'resume.bin');
  writeFileSync(large, Buffer.alloc(120 * MB, 3)); // 8 parts of 16 MB
  const resumeFolder = `${folder} resume`;

  await page.goto('/login');
  await page.getByLabel('Username').fill(E2E_USER);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);
  await page.goto('/admin/files');
  await page.getByRole('button', { name: 'New folder' }).click();
  await page.getByRole('dialog').getByLabel('Folder name').fill(resumeFolder);
  await page.getByRole('dialog').getByRole('button', { name: 'Create' }).click();
  await page.getByRole('button', { name: resumeFolder }).click();
  await expect(page).toHaveURL(/folder=/);

  // "Network lost" from part 4 on
  const partOf = (url: string) => Number(new URL(url).searchParams.get('partNumber'));
  await page.route(/partNumber=/, (route) =>
    partOf(route.request().url()) >= 4 ? route.abort('internetdisconnected') : route.continue(),
  );
  await page.getByTestId('media-file-input').setInputFiles(large);
  await expect(page.getByRole('listitem').filter({ hasText: 'resume.bin' })).toContainText('Failed', {
    timeout: 60_000,
  });

  await page.unroute(/partNumber=/);
  await page.reload();
  const unfinished = page.getByRole('listitem').filter({ hasText: 'resume.bin' });
  await expect(unfinished).toContainText('40%'); // 3 parts × 16 MB of 120 MB
  const sent: number[] = [];
  page.on('request', (request) => {
    if (request.method() === 'PUT' && request.url().includes('partNumber=')) sent.push(partOf(request.url()));
  });

  const chooser = page.waitForEvent('filechooser');
  await unfinished.getByRole('button', { name: 'Resume' }).click();
  await (await chooser).setFiles(large);

  await expect(page.getByText('resume.bin is ready')).toBeVisible({ timeout: 60_000 });
  await expect(page.getByRole('row').filter({ hasText: 'resume.bin' })).toContainText('120 MB');
  expect(sent.sort((a, b) => a - b)).toEqual([4, 5, 6, 7, 8]);

  await page.getByRole('button', { name: 'Files', exact: true }).first().click();
  await page.getByRole('row').filter({ hasText: resumeFolder }).getByRole('button', { name: 'Delete' }).click();
  await page.getByRole('alertdialog').getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByRole('button', { name: resumeFolder })).toBeHidden();
});
