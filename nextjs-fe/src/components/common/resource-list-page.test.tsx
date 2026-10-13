import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import {
  ResourceListPage,
  type ResourceFormProps,
  type ResourceListPageProps,
} from './resource-list-page';
import type { Column } from '@/components/common/data-table/data-table';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

// A stable translator that returns the key, so assertions can use message keys.
vi.mock('next-intl', () => {
  const t = (key: string) => key;
  return { useTranslations: () => t };
});
// Permissions of the signed-in admin (ADR-0013); each test sets them
let granted: string[] = [];
vi.mock('@/shared/hooks/use-permissions', () => ({
  usePermissions: () => ({ can: (permission: string) => granted.includes(permission) }),
}));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn() },
}));

const ENDPOINT = '/admin/admin-mst';

interface Row {
  id: number;
  title: string;
}

const columns: Column<Row>[] = [
  { key: 'id', label: 'id' },
  { key: 'title', label: 'title' },
];

const submitted = vi.fn();

function TestForm({ initialData, formId, onSuccess }: ResourceFormProps<Row>) {
  return (
    <form
      id={formId}
      onSubmit={(event) => {
        event.preventDefault();
        submitted(initialData?.id ?? null);
        onSuccess();
      }}
    >
      form for {initialData?.title ?? 'new'}
    </form>
  );
}

const renderPage = (dialogActions?: ResourceListPageProps<Row>['dialogActions']) => {
  const { Wrapper } = createQueryWrapper();
  return render(
    <Wrapper>
      <ResourceListPage<Row>
        endpoint={ENDPOINT}
        entity={{ one: 'admin', many: 'admins' }}
        columns={columns}
        filterFields={[]}
        searchFields={[]}
        defaultSortBy="rank_order"
        filtersKey="test-filters"
        form={TestForm}
        dialogSize="lg"
        dialogActions={dialogActions}
      />
    </Wrapper>,
  );
};

describe('ResourceListPage', () => {
  let listParams: URLSearchParams[];

  beforeEach(() => {
    granted = ['admin-mst.list', 'admin-mst.store', 'admin-mst.update', 'admin-mst.delete'];
    listParams = [];
    submitted.mockClear();
    server.use(
      http.get(apiUrl(`${ENDPOINT}/list`), ({ request }) => {
        listParams.push(new URL(request.url).searchParams);
        return HttpResponse.json(
          envelope({
            data: [
              { id: 1, title: 'Spring sale' },
              { id: 2, title: 'Launch' },
            ],
            current_page: 1,
            per_page: 10,
            total: 2,
          }),
        );
      }),
    );
  });

  it('lists the resource with the default sort', async () => {
    renderPage();

    await screen.findByText('Spring sale');
    screen.getByText('Launch');
    expect(listParams[0].get('sort_by')).toBe('rank_order');
    expect(listParams[0].get('sort_order')).toBe('asc');
  });

  it('opens the form empty for create and filled for edit', async () => {
    renderPage();
    await screen.findByText('Spring sale');

    fireEvent.click(screen.getByRole('button', { name: /createEntity/ }));
    await screen.findByText('form for new');
    fireEvent.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'cancel' }));

    fireEvent.click(screen.getAllByRole('button', { name: 'edit' })[0]);
    await screen.findByText('form for Spring sale');
  });

  it('deletes a row after confirmation', async () => {
    let deleted: unknown;
    server.use(
      http.post(apiUrl(`${ENDPOINT}/delete`), async ({ request }) => {
        deleted = await request.json();
        return HttpResponse.json(envelope(null));
      }),
    );
    renderPage();
    await screen.findByText('Launch');

    fireEvent.click(screen.getAllByRole('button', { name: 'delete' })[1]);
    const dialog = await screen.findByRole('alertdialog');
    fireEvent.click(within(dialog).getByRole('button', { name: 'delete' }));

    await waitFor(() => expect(deleted).toEqual({ ids: [2] }));
  });

  it('hides create, edit and delete without their permissions', async () => {
    granted = ['admin-mst.list'];
    renderPage();
    await screen.findByText('Spring sale');

    expect(screen.queryByRole('button', { name: /createEntity/ })).toBeNull();
    expect(screen.queryByRole('button', { name: 'edit' })).toBeNull();
    expect(screen.queryByRole('button', { name: 'delete' })).toBeNull();
    expect(screen.queryByRole('checkbox')).toBeNull();
  });

  it('submits the form from the footer: create on new, update on edit', async () => {
    renderPage();
    await screen.findByText('Spring sale');

    fireEvent.click(screen.getByRole('button', { name: /createEntity/ }));
    fireEvent.click(
      within(await screen.findByRole('dialog')).getByRole('button', { name: 'create' }),
    );
    await waitFor(() => expect(submitted).toHaveBeenLastCalledWith(null));
    await waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());

    fireEvent.click(screen.getAllByRole('button', { name: 'edit' })[0]);
    fireEvent.click(
      within(await screen.findByRole('dialog')).getByRole('button', { name: 'update' }),
    );
    await waitFor(() => expect(submitted).toHaveBeenLastCalledWith(1));
  });

  it('renders the page own footer buttons and hides those without permission', async () => {
    const archive = vi.fn();
    renderPage(({ record, submitAction }) => [
      { key: 'archive', label: 'archive', onClick: () => archive(record?.id) },
      { key: 'export', label: 'export', permission: 'admin-mst.export', onClick: vi.fn() },
      submitAction,
    ]);
    await screen.findByText('Spring sale');

    fireEvent.click(screen.getAllByRole('button', { name: 'edit' })[0]);
    const dialog = await screen.findByRole('dialog');
    expect(within(dialog).queryByRole('button', { name: 'export' })).toBeNull();
    // Buttons render left to right in the declared order, after Cancel
    expect(
      within(dialog)
        .getAllByRole('button')
        .map((b) => b.textContent)
        .filter((name) => name !== 'close'),
    ).toEqual(['cancel', 'archive', 'update']);
    fireEvent.click(within(dialog).getByRole('button', { name: 'archive' }));

    await waitFor(() => expect(archive).toHaveBeenCalledWith(1));
  });
});
