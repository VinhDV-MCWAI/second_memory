import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { ResourceListPage, type ResourceFormProps } from './resource-list-page';
import type { Column } from '@/components/common/data-table/data-table';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

// A stable translator that returns the key, so assertions can use message keys.
vi.mock('next-intl', () => {
  const t = (key: string) => key;
  return { useTranslations: () => t };
});
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn() },
}));

const ENDPOINT = '/admin/banner-mgmt';

interface Row {
  id: number;
  title: string;
}

const columns: Column<Row>[] = [
  { key: 'id', label: 'id' },
  { key: 'title', label: 'title' },
];

function TestForm({ initialData }: ResourceFormProps<Row>) {
  return <p>form for {initialData?.title ?? 'new'}</p>;
}

const renderPage = () => {
  const { Wrapper } = createQueryWrapper();
  return render(
    <Wrapper>
      <ResourceListPage<Row>
        endpoint={ENDPOINT}
        entity={{ one: 'banner', many: 'banners' }}
        columns={columns}
        filterFields={[]}
        searchFields={[]}
        defaultSortBy="rank_order"
        filtersKey="test-filters"
        form={TestForm}
        dialogClassName="max-w-xl"
      />
    </Wrapper>,
  );
};

describe('ResourceListPage', () => {
  let listParams: URLSearchParams[];

  beforeEach(() => {
    listParams = [];
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
    fireEvent.click(
      within(screen.getByRole('dialog')).getAllByRole('button', { name: 'cancel' })[0],
    );

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
});
