import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import { MediaBrowser } from './media-browser';

vi.mock('next-intl', () => {
  const t = (key: string) => key;
  return { useTranslations: () => t };
});
let granted: string[] = [];
vi.mock('@/shared/hooks/use-permissions', () => ({
  usePermissions: () => ({ can: (permission: string) => granted.includes(permission) }),
}));
const notification = vi.hoisted(() => ({ success: vi.fn(), error: vi.fn() }));
vi.mock('@/shared/utils/notification', () => ({ notification }));
const push = vi.fn();
let search = new URLSearchParams();
vi.mock('next/navigation', () => ({ useRouter: () => ({ push }), useSearchParams: () => search }));
const uploadFile = vi.hoisted(() => vi.fn());
const discardUpload = vi.hoisted(() => vi.fn());
vi.mock('../lib/uploader', () => ({ uploadFile, discardUpload }));

const ALL = [
  'media.list',
  'media.store-folder',
  'media.upload-url',
  'media.multipart-start',
  'media.multipart-unfinished',
  'media.download-url',
  'media.update',
  'media.delete',
];
const item = (
  id: number,
  name: string,
  isFile: boolean,
  path = `/${name}`,
  preview: string | null = null,
) => ({
  id,
  is_file: isFile,
  name,
  path,
  extension: null,
  mime_type: null,
  size: isFile ? 2048 : null,
  upload_status: 2,
  preview,
  created_at: null,
  updated_at: null,
});
const page = (rows: unknown[]) =>
  envelope({
    data: rows,
    links: {},
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: 50,
      total: rows.length,
      from: 1,
      to: rows.length,
    },
  });

function renderBrowser() {
  const { Wrapper } = createQueryWrapper();
  return render(<MediaBrowser />, { wrapper: Wrapper });
}

const unfinishedMovie = {
  id: 7,
  name: 'movie.mkv',
  folder: '/',
  size: 300,
  mime_type: 'video/x-matroska',
  uploaded_bytes: 150,
  updated_at: '',
};

describe('MediaBrowser', () => {
  const listed = vi.fn();
  let unfinished: unknown[] = [];

  beforeEach(() => {
    granted = ALL;
    search = new URLSearchParams();
    unfinished = [];
    vi.clearAllMocks();
    server.use(
      http.get(apiUrl('/admin/media/multipart/unfinished'), () =>
        HttpResponse.json(envelope(unfinished)),
      ),
      http.get(apiUrl('/admin/media/list'), ({ request }) => {
        listed(new URL(request.url).searchParams.get('folder'));
        return HttpResponse.json(page([item(1, 'Docs', false), item(2, 'notes.txt', true)]));
      }),
    );
  });

  it('lists the open folder and opens a sub-folder through the URL', async () => {
    search = new URLSearchParams('folder=/Work');
    renderBrowser();

    await screen.findByText('notes.txt');
    expect(listed).toHaveBeenCalledWith('/Work');
    screen.getByText('Work');

    fireEvent.click(screen.getByRole('button', { name: 'Docs' }));
    expect(push).toHaveBeenCalledWith('/admin/files?folder=%2FDocs');
  });

  it('uploads the chosen files into the open folder', async () => {
    uploadFile.mockResolvedValue({ kind: 'completed', file: item(3, 'a.txt', true) });
    renderBrowser();
    await screen.findByText('notes.txt');

    const file = new File(['hi'], 'a.txt', { type: 'text/plain' });
    fireEvent.change(screen.getByTestId('media-file-input'), { target: { files: [file] } });

    await waitFor(() =>
      expect(uploadFile).toHaveBeenCalledWith(file, expect.objectContaining({ folder: '/' })),
    );
    await screen.findByText('states.done');
  });

  it('shows only what the permissions allow', async () => {
    granted = ['media.list', 'media.download-url'];
    renderBrowser();
    await screen.findByText('notes.txt');

    expect(screen.queryByRole('button', { name: /upload/ })).toBeNull();
    expect(screen.queryByRole('button', { name: /newFolder/ })).toBeNull();
    expect(screen.queryByRole('button', { name: 'rename' })).toBeNull();
    expect(screen.getAllByRole('button', { name: 'download' })).toHaveLength(1);
  });

  it('deletes after confirming', async () => {
    const deleted = vi.fn();
    server.use(
      http.post(apiUrl('/admin/media/delete'), async ({ request }) => {
        deleted(await request.json());
        return HttpResponse.json(envelope(null));
      }),
    );
    renderBrowser();
    await screen.findByText('notes.txt');

    fireEvent.click(screen.getAllByRole('button', { name: 'delete' })[1]);
    fireEvent.click(await screen.findByRole('button', { name: 'delete' }));

    await waitFor(() => expect(deleted).toHaveBeenCalledWith({ ids: [2] }));
  });

  it('shows where a file is attached and warns before deleting it', async () => {
    server.use(
      http.get(apiUrl('/admin/media/list'), () =>
        HttpResponse.json(
          page([
            { ...item(1, 'Docs', false), attached_count: 0, used_by: [] },
            {
              ...item(2, 'cert.pdf', true),
              attached_count: 1,
              used_by: [{ id: 9, title: 'AWS exam' }],
            },
          ]),
        ),
      ),
    );
    renderBrowser();

    expect(await screen.findByLabelText('file')).toBeTruthy();
    fireEvent.click(screen.getAllByRole('button', { name: 'delete' })[1]);
    expect(await screen.findByText(/usedBy.deleteWarning/)).toBeTruthy();
  });

  it('links an attached file to its evidence when evidence can be listed', async () => {
    granted = [...ALL, 'evidence.list'];
    server.use(
      http.get(apiUrl('/admin/media/list'), () =>
        HttpResponse.json(
          page([
            { ...item(1, 'Docs', false), attached_count: 3, used_by: [] },
            {
              ...item(2, 'cert 1.pdf', true),
              attached_count: 1,
              used_by: [{ id: 9, title: 'AWS exam' }],
            },
          ]),
        ),
      ),
    );
    renderBrowser();

    const link = await screen.findByRole('link', { name: 'open' });
    expect(link.getAttribute('href')).toBe('/admin/evidence?media_id=2&file=cert+1.pdf');
    // A folder only counts, it has no single file to filter by
    expect(screen.getAllByRole('link')).toHaveLength(1);
  });

  it('resumes an unfinished upload when the same file is uploaded into the same folder', async () => {
    unfinished = [unfinishedMovie];
    uploadFile.mockReturnValue(new Promise(() => undefined));
    renderBrowser();
    await screen.findByText('50% · 150 B / 300 B');

    const file = new File(['x'.repeat(300)], 'movie.mkv');
    fireEvent.change(screen.getByTestId('media-file-input'), { target: { files: [file] } });

    await waitFor(() =>
      expect(uploadFile).toHaveBeenCalledWith(file, expect.objectContaining({ resumeId: 7 })),
    );
  });

  it('asks for the same file to resume and can discard an unfinished upload', async () => {
    unfinished = [unfinishedMovie];
    discardUpload.mockResolvedValue(undefined);
    renderBrowser();
    await screen.findByText('movie.mkv');

    fireEvent.click(screen.getByRole('button', { name: 'resume' }));
    const other = new File(['short'], 'movie.mkv');
    fireEvent.change(screen.getByTestId('media-resume-input'), { target: { files: [other] } });
    expect(notification.error).toHaveBeenCalledWith('unfinished.wrongFile');
    expect(uploadFile).not.toHaveBeenCalled();

    fireEvent.click(screen.getByRole('button', { name: 'discard' }));
    fireEvent.click(await screen.findByRole('button', { name: 'delete' }));
    await waitFor(() => expect(discardUpload).toHaveBeenCalledWith(7));
    expect(notification.success).toHaveBeenCalledWith('unfinished.discarded');
  });

  it('previews an image and a text file without downloading them', async () => {
    const inline = vi.fn();
    server.use(
      http.get(apiUrl('/admin/media/list'), () =>
        HttpResponse.json(
          page([
            item(4, 'photo.jpg', true, '/photo.jpg', 'image'),
            item(5, 'notes.md', true, '/notes.md', 'text'),
            item(6, 'setup.zip', true),
          ]),
        ),
      ),
      http.get(apiUrl('/admin/media/download-url/:id'), ({ params, request }) => {
        inline(new URL(request.url).searchParams.get('inline'));
        return HttpResponse.json(
          envelope({ url: `http://storage.test/file-${String(params.id)}`, expires_at: '' }),
        );
      }),
      http.get('http://storage.test/file-5', () => HttpResponse.text('# Hello <script>x</script>')),
    );
    renderBrowser();
    await screen.findByText('setup.zip');

    // Only files with a preview get the button
    expect(screen.getAllByRole('button', { name: 'preview.open' })).toHaveLength(2);

    fireEvent.click(screen.getByRole('button', { name: 'photo.jpg' }));
    const image = await screen.findByRole('img', { name: 'photo.jpg' });
    expect(image.getAttribute('src')).toBe('http://storage.test/file-4');
    expect(inline).toHaveBeenCalledWith('1');
    fireEvent.keyDown(document.activeElement ?? document.body, { key: 'Escape' });
    await waitFor(() => expect(screen.queryByRole('img', { name: 'photo.jpg' })).toBeNull());

    fireEvent.click(screen.getAllByRole('button', { name: 'preview.open' })[1]);
    // Shown as text: the markup is not interpreted
    await screen.findByText('# Hello <script>x</script>');
  });
});
