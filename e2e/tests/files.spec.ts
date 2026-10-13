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
