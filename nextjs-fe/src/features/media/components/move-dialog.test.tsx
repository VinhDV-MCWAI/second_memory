import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import type { MediaItem } from '@/shared/types/models';
import { MoveDialog } from './move-dialog';

vi.mock('next-intl', () => {
  const t = (key: string, params?: Record<string, string>) =>
    params ? `${key}:${Object.values(params).join(',')}` : key;
  return { useTranslations: () => t };
});

vi.mock('@/shared/hooks/use-permissions', () => ({ usePermissions: () => ({ can: () => true }) }));

const folder = (id: number, path: string, isFile = false) =>
  ({ id, is_file: isFile, name: path.split('/').pop(), path }) as MediaItem;

// "/" holds Docs and a file; Docs holds 2026
const children: Record<string, MediaItem[]> = {
  '/': [folder(1, '/Docs'), folder(9, '/readme.md', true)],
  '/Docs': [folder(2, '/Docs/2026')],
  '/Docs/2026': [],
};

const renderDialog = (item: MediaItem, onMove = vi.fn().mockResolvedValue(1)) => {
  server.use(
    http.get(apiUrl('/admin/media/list'), ({ request }) => {
      const data = children[new URL(request.url).searchParams.get('folder') ?? '/'] ?? [];
      return HttpResponse.json(
        envelope({ data, current_page: 1, last_page: 1, per_page: 100, total: data.length }),
      );
    }),
  );
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <MoveDialog item={item} onClose={vi.fn()} onMove={onMove} />
    </Wrapper>,
  );
  return onMove;
};

describe('MoveDialog', () => {
  it('moves a file into a folder picked in the tree, folders only', async () => {
    const onMove = renderDialog(folder(9, '/readme.md', true));

    const move = screen.getByRole('button', { name: 'move' });
    expect(move).toHaveProperty('disabled', true);
    fireEvent.click(await screen.findByRole('button', { name: 'expand:Docs' }));
    fireEvent.click(await screen.findByRole('button', { name: '2026' }));
    expect(screen.queryByRole('button', { name: 'readme.md' })).toBeNull();
    fireEvent.click(move);

    await waitFor(() => expect(onMove).toHaveBeenCalledWith(9, '/Docs/2026'));
  });

  it('does not let a folder move into itself or below it', async () => {
    renderDialog(folder(1, '/Docs'));

    fireEvent.click(await screen.findByRole('button', { name: 'expand:Docs' }));
    expect(screen.getByRole('button', { name: 'Docs' })).toHaveProperty('disabled', true);
    expect(await screen.findByRole('button', { name: '2026' })).toHaveProperty('disabled', true);
  });

  it('shows the error the server sends back', async () => {
    renderDialog(
      folder(9, '/readme.md', true),
      vi.fn().mockRejectedValue({
        response: { data: { error: { status: true, code: 422, messages: { folder: ['Taken'] } } } },
      }),
    );

    fireEvent.click(await screen.findByRole('button', { name: 'Docs' }));
    fireEvent.click(screen.getByRole('button', { name: 'move' }));

    expect(await screen.findByText('Taken')).toBeTruthy();
  });
});
