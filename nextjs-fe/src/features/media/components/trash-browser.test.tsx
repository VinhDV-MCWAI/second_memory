import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import { TrashBrowser } from './trash-browser';

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

const trashed = (id: number, name: string) => ({
  id,
  name,
  path: `/Docs/${name}`,
  size: 2048,
  mime_type: 'text/plain',
  deleted_at: '2026-10-15T08:00:00+00:00',
  deleted_by: 'root',
  expires_at: '2026-11-14T08:00:00+00:00',
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

function renderTrash() {
  const { Wrapper } = createQueryWrapper();
  return render(<TrashBrowser />, { wrapper: Wrapper });
}

describe('TrashBrowser', () => {
  let rows: unknown[] = [];

  beforeEach(() => {
    granted = [
      'media.trash-list',
      'media.trash-restore',
      'media.trash-delete',
      'media.trash-empty',
    ];
    rows = [trashed(1, 'report.pdf'), trashed(2, 'notes.txt')];
    vi.clearAllMocks();
    server.use(http.get(apiUrl('/admin/media/trash/list'), () => HttpResponse.json(page(rows))));
  });

  it('restores a file and says where it went', async () => {
    const restored = vi.fn();
    server.use(
      http.post(apiUrl('/admin/media/trash/restore/:id'), ({ params }) => {
        restored(params.id);
        rows = [trashed(2, 'notes.txt')];
        return HttpResponse.json(envelope({ data: { id: 1, path: '/Docs/report.pdf' } }));
      }),
    );
    renderTrash();
    await screen.findByText('report.pdf');
    screen.getByText('/Docs/report.pdf');

    fireEvent.click(screen.getAllByRole('button', { name: 'restore' })[0]);

    await waitFor(() => expect(restored).toHaveBeenCalledWith('1'));
    expect(notification.success).toHaveBeenCalledWith('restored');
    await waitFor(() => expect(screen.queryByText('report.pdf')).toBeNull());
  });

  it('deletes for good and empties the trash after confirming', async () => {
    const purged = vi.fn();
    const emptied = vi.fn();
    server.use(
      http.post(apiUrl('/admin/media/trash/delete'), async ({ request }) => {
        purged(await request.json());
        return HttpResponse.json(envelope(1));
      }),
      http.post(apiUrl('/admin/media/trash/empty'), () => {
        emptied();
        return HttpResponse.json(envelope(2));
      }),
    );
    renderTrash();
    await screen.findByText('report.pdf');

    fireEvent.click(screen.getAllByRole('button', { name: 'deleteForever' })[1]);
    fireEvent.click(await screen.findByRole('button', { name: 'deleteForever' }));
    await waitFor(() => expect(purged).toHaveBeenCalledWith({ ids: [2] }));

    fireEvent.click(screen.getByRole('button', { name: 'empty' }));
    fireEvent.click(await screen.findByRole('button', { name: 'empty' }));
    await waitFor(() => expect(emptied).toHaveBeenCalledOnce());
  });

  it('only lists for an admin who may not change the trash', async () => {
    granted = ['media.trash-list'];
    renderTrash();
    await screen.findByText('report.pdf');

    expect(screen.queryByRole('button', { name: 'restore' })).toBeNull();
    expect(screen.queryByRole('button', { name: 'deleteForever' })).toBeNull();
    expect(screen.queryByRole('button', { name: 'empty' })).toBeNull();
  });

  it('says when the trash is empty', async () => {
    rows = [];
    renderTrash();
    await screen.findByText('nothing');
  });
});
