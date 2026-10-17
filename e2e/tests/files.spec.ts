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
  // The Unfinished uploads card may already list it too, so look for the failed row of the queue
  await expect(
    page.getByRole('listitem').filter({ hasText: 'resume.bin' }).filter({ hasText: 'Failed' }),
  ).toBeVisible({ timeout: 60_000 });

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

// Preview (MEDIA-06): an image renders in the dialog and a text file shows as text, both straight
// from MinIO through nginx with the type the server chose.
test('owner previews an image and a text file; images and videos get thumbnails and lengths', async ({ page }) => {
  const dir = mkdtempSync(join(tmpdir(), 'e2e-preview-'));
  const image = join(dir, 'dot.png');
  const text = join(dir, 'readme.md');
  // 1×1 red PNG
  writeFileSync(
    image,
    Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==', 'base64'),
  );
  writeFileSync(text, '# Preview from e2e <b>not bold</b>');
  // 16×16 blue WebM, 3 seconds (made with ffmpeg: color=c=blue:s=16x16:r=1:d=3, libvpx)
  const video = join(dir, 'tiny.webm');
  writeFileSync(
    video,
    Buffer.from(
      'GkXfo59ChoEBQveBAULygQRC84EIQoKEd2VibUKHgQJChYECGFOAZwEAAAAAAAJkEU2bdLpNu4tTq4QVSalmU6yBoU27i1OrhBZUrmtTrIHYTbuMU6uEElTDZ1OsggElTbuMU6uEHFO7a1OsggJO7AEAAAAAAABZAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAVSalmsirXsYMPQkBNgI1MYXZmNjIuMTIuMTAyV0GNTGF2ZjYyLjEyLjEwMkSJiECncAAAAAAAFlSua8iuAQAAAAAAAD/XgQFzxYhgFoEJ3chFqJyBACK1nIN1bmSIgQCGhVZfVlA4g4EBI+ODhDuaygDgkLCBELqBEJqBAlWwhFW5gQESVMNn/HNzoGPAgGfImkWjh0VOQ09ERVJEh41MYXZmNjIuMTIuMTAyc3PWY8CLY8WIYBaBCd3IRahnyKFFo4dFTkNPREVSRIeUTGF2YzYyLjI4LjEwMiBsaWJ2cHhnyKFFo4hEVVJBVElPTkSHkzAwOjAwOjAzLjAwMDAwMDAwMAAfQ7Z1QKLngQCjvYEAAICwAgCdASoQABAAAEcIhYWImYSIAgICdaoD+AIM/fsA/v9NEv/8SqfxKp/Eqn/xKp/9GH8MP4Yf3hCjroED6ACxAQABEBAAGAAwP/QMAO8A/v9NEv/8SqfxKp/Eqn/xKp/9GH8MP4Yf3hCjroEH0ACxAQABEBAAGAAwP/QMAO8A/v9NEv/8SqfxKp/Eqn/xKp/9GH8MP4Yf3hAcU7trkbuPs4EAt4r3gQHxggGm8IED',
      'base64',
    ),
  );
  const previewFolder = `${folder} preview`;

  await page.goto('/login');
  await page.getByLabel('Username').fill(E2E_USER);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);
  await page.goto('/admin/files');
  await page.getByRole('button', { name: 'New folder' }).click();
  await page.getByRole('dialog').getByLabel('Folder name').fill(previewFolder);
  await page.getByRole('dialog').getByRole('button', { name: 'Create' }).click();
  await page.getByRole('button', { name: previewFolder }).click();
  await expect(page).toHaveURL(/folder=/);

  await page.getByTestId('media-file-input').setInputFiles([image, text, video]);
  // Width × height read from the start of the object in MinIO (MEDIA-10)
  await expect(page.getByRole('row').filter({ hasText: 'dot.png' })).toContainText('1 × 1');
  // Thumbnails and length from ffmpeg in the queue worker; the listing asks again until they arrive (MEDIA-11)
  await expect(page.getByRole('row').filter({ hasText: 'dot.png' }).locator('img')).toBeVisible();
  const videoRow = page.getByRole('row').filter({ hasText: 'tiny.webm' });
  await expect(videoRow).toContainText('16 × 16 · 0:03');
  await expect(videoRow.locator('img')).toBeVisible();
  // Visible before it has loaded: wait for the decoded size
  await expect
    .poll(() => videoRow.locator('img').evaluate((img: HTMLImageElement) => img.naturalWidth))
    .toBe(16);
  await page.getByRole('button', { name: 'dot.png' }).click();
  const shown = page.getByRole('dialog').getByRole('img', { name: 'dot.png' });
  await expect(shown).toBeVisible();
  await expect.poll(() => shown.evaluate((img: HTMLImageElement) => img.naturalWidth)).toBe(1);
  // Read by the browser: the signed URL is for the host it uses (the runner's container cannot reach it)
  const headers = await shown.evaluate(async (img: HTMLImageElement) => {
    const response = await fetch(img.src);
    return Object.fromEntries(response.headers.entries());
  });
  expect(headers['content-type']).toBe('image/png');
  expect(headers['x-content-type-options']).toContain('nosniff'); // MinIO and nginx both send it
  expect(headers['content-disposition']).toContain('inline');
  await page.keyboard.press('Escape');

  await page.getByRole('row').filter({ hasText: 'readme.md' }).getByRole('button', { name: 'Preview' }).click();
  await expect(page.getByRole('dialog').locator('pre')).toHaveText('# Preview from e2e <b>not bold</b>');
  await page.keyboard.press('Escape');

  await page.getByRole('button', { name: 'Files', exact: true }).first().click();
  await page.getByRole('row').filter({ hasText: previewFolder }).getByRole('button', { name: 'Delete' }).click();
  await page.getByRole('alertdialog').getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByRole('button', { name: previewFolder })).toBeHidden();
});

// Trash (MEDIA-07): a deleted file is restored into its folder, recreated because it was deleted too;
// deleting it again and then for good leaves nothing in the trash.
test('owner restores a deleted file from the trash, then deletes it for good', async ({ page }) => {
  const dir = mkdtempSync(join(tmpdir(), 'e2e-trash-'));
  const note = join(dir, 'kept.txt');
  writeFileSync(note, 'restore me');
  const trashFolder = `${folder} trash`;

  await page.goto('/login');
  await page.getByLabel('Username').fill(E2E_USER);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);
  await page.goto('/admin/files');
  await page.getByRole('button', { name: 'New folder' }).click();
  await page.getByRole('dialog').getByLabel('Folder name').fill(trashFolder);
  await page.getByRole('dialog').getByRole('button', { name: 'Create' }).click();
  await page.getByRole('button', { name: trashFolder }).click();
  await expect(page).toHaveURL(/folder=/);
  await page.getByTestId('media-file-input').setInputFiles(note);
  await expect(page.getByRole('cell', { name: 'kept.txt' })).toBeVisible();

  // Delete the whole folder
  await page.getByRole('button', { name: 'Files', exact: true }).first().click();
  await page.getByRole('row').filter({ hasText: trashFolder }).getByRole('button', { name: 'Delete' }).click();
  await page.getByRole('alertdialog').getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByRole('button', { name: trashFolder })).toBeHidden();

  await page.getByRole('link', { name: 'Trash' }).click();
  await expect(page).toHaveURL(/\/admin\/files\/trash$/);
  const row = page.getByRole('row').filter({ hasText: `/${trashFolder}/kept.txt` });
  await row.getByRole('button', { name: 'Restore' }).click();
  await expect(page.getByText(`Restored to /${trashFolder}/kept.txt`)).toBeVisible();

  // Back in its recreated folder, and the bytes came back in MinIO
  await page.getByRole('link', { name: 'Back to files' }).click();
  await expect(page).toHaveURL(/\/admin\/files$/);
  await page.goto(`/admin/files?folder=${encodeURIComponent(`/${trashFolder}`)}`);
  const restored = page.getByRole('row').filter({ hasText: 'kept.txt' });
  const download = page.waitForEvent('download');
  await restored.getByRole('button', { name: 'Download' }).click();
  expect((await download).suggestedFilename()).toBe('kept.txt');

  await restored.getByRole('button', { name: 'Delete' }).click();
  await page.getByRole('alertdialog').getByRole('button', { name: 'Delete' }).click();
  await page.goto('/admin/files/trash');
  await page.getByRole('row').filter({ hasText: `/${trashFolder}/kept.txt` }).getByRole('button', { name: 'Delete forever' }).click();
  await page.getByRole('alertdialog').getByRole('button', { name: 'Delete forever' }).click();
  await expect(page.getByText('Deleted forever')).toBeVisible();
  await expect(page.getByRole('row').filter({ hasText: `/${trashFolder}/kept.txt` })).toBeHidden();

  await page.goto('/admin/files');
  await page.getByRole('row').filter({ hasText: trashFolder }).getByRole('button', { name: 'Delete' }).click();
  await page.getByRole('alertdialog').getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByRole('button', { name: trashFolder })).toBeHidden();
});
