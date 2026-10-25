import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { CategoryTreeView } from './category-tree-view';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

vi.mock('next-intl', () => ({
  useTranslations: () => (key: string, params?: Record<string, string>) =>
    params ? `${key}:${params.name}` : key,
}));
vi.mock('@/shared/hooks/use-permissions', () => ({ usePermissions: () => ({ can: () => true }) }));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn() },
}));

const row = (id: number, parentId: number | null, name: string) => ({
  id,
  parent_id: parentId,
  name,
  article_count: 0,
  is_public: true,
});

// Tree order, as the API sends it
const rows = [
  row(1, null, 'Kỹ năng'),
  row(2, 1, 'Laravel'),
  row(3, 2, 'Hàng đợi'),
  row(4, null, 'Quy trình'),
];

const renderTree = (edit = vi.fn()) => {
  server.use(
    http.get(apiUrl('/admin/content-category/list'), () =>
      HttpResponse.json(
        envelope({ data: rows, current_page: 1, last_page: 1, per_page: 1000, total: 4 }),
      ),
    ),
  );
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <CategoryTreeView edit={edit} remove={null} />
    </Wrapper>,
  );
};

const names = () => screen.getAllByRole('treeitem').map((item) => item.textContent);

describe('CategoryTreeView', () => {
  it('folds a branch, and every branch at once', async () => {
    renderTree();
    expect(await screen.findByText('Hàng đợi')).toBeTruthy();

    fireEvent.click(screen.getByRole('button', { name: 'collapseNamed:Laravel' }));
    expect(screen.queryByText('Hàng đợi')).toBeNull();
    fireEvent.click(screen.getByRole('button', { name: 'expandNamed:Laravel' }));
    expect(screen.getByText('Hàng đợi')).toBeTruthy();

    fireEvent.click(screen.getByRole('button', { name: 'collapseAll' }));
    expect(names().map((name) => name?.includes('Kỹ năng') || name?.includes('Quy trình'))).toEqual(
      [true, true],
    );
    fireEvent.click(screen.getByRole('button', { name: 'expandAll' }));
    expect(screen.getAllByRole('treeitem')).toHaveLength(4);
  });

  it('searches without accents and keeps the categories above a match; no drag meanwhile', async () => {
    renderTree();
    await screen.findByText('Hàng đợi');
    expect(screen.getAllByRole('button', { name: /^dragNamed:/ })).toHaveLength(4);

    fireEvent.change(screen.getByRole('textbox', { name: 'searchCategories' }), {
      target: { value: 'hang doi' },
    });
    expect(screen.getAllByRole('treeitem')).toHaveLength(3);
    expect(screen.queryByText('Quy trình')).toBeNull();
    expect(screen.queryByRole('button', { name: /^dragNamed:/ })).toBeNull();
    expect(screen.getByText('dragNotWhileSearching')).toBeTruthy();
  });

  it('opens a row in the edit dialog', async () => {
    const edit = vi.fn();
    renderTree(edit);
    const item = (await screen.findByText('Laravel')).closest('[role="treeitem"]');
    fireEvent.click(within(item as HTMLElement).getByRole('button', { name: 'edit' }));
    expect(edit).toHaveBeenCalledWith(expect.objectContaining({ id: 2 }));
  });
});
