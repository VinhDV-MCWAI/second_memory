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
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn() },
}));
const push = vi.fn();
let search = new URLSearchParams();
vi.mock('next/navigation', () => ({ useRouter: () => ({ push }), useSearchParams: () => search }));
const uploadFile = vi.hoisted(() => vi.fn());
vi.mock('../lib/uploader', () => ({ uploadFile }));

const ALL = [
  'media.list',
  'media.store-folder',
  'media.upload-url',
  'media.multipart-start',
  'media.download-url',
  'media.update',
  'media.delete',
];
const item = (id: number, name: string, isFile: boolean, path = `/${name}`) => ({
  id,
  is_file: isFile,
  name,
  path,
  extension: null,
  mime_type: null,
  size: isFile ? 2048 : null,
  upload_status: 2,
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

describe('MediaBrowser', () => {
  const listed = vi.fn();

  beforeEach(() => {
    granted = ALL;
    search = new URLSearchParams();
    vi.clearAllMocks();
    server.use(
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
});
