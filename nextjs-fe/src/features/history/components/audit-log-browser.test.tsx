import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { AuditLogBrowser } from './audit-log-browser';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

vi.mock('next-intl', () => {
  const t = (key: string, params?: Record<string, unknown>) =>
    params ? `${key}:${JSON.stringify(params)}` : key;
  return { useTranslations: () => t };
});

let permissions: string[] = ['audit-log.list'];
vi.mock('@/shared/hooks/use-permissions', () => ({
  usePermissions: () => ({ can: (permission: string) => permissions.includes(permission) }),
}));

const page = (data: unknown[]) =>
  envelope({ data, meta: { current_page: 1, last_page: 1, per_page: 10, total: data.length } });

const entry = {
  id: 9,
  auditable_type: 'skill',
  auditable_id: 4,
  event: 'updated',
  old_values: { name: 'PHP' },
  new_values: { name: 'Laravel' },
  admin_mst_id: 1,
  actor_user_name: 'root',
  ip_address: '172.18.0.1',
  created_at: '2026-10-14T10:00:00+00:00',
};

function renderBrowser() {
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <AuditLogBrowser />
    </Wrapper>,
  );
}

describe('AuditLogBrowser', () => {
  it('lists every row newest first with who, what and the changed fields', async () => {
    permissions = ['audit-log.list'];
    let query: URLSearchParams | undefined;
    server.use(
      http.get(apiUrl('/admin/audit-log/list'), ({ request }) => {
        query = new URL(request.url).searchParams;
        return HttpResponse.json(page([entry]));
      }),
    );

    renderBrowser();

    await screen.findByText('types.skill #4');
    screen.getByText('events.updated');
    screen.getByText('root');
    screen.getByText('172.18.0.1');
    screen.getByText('fieldCount:{"count":1}');
    screen.getByText('Laravel');
    expect(query?.get('sort_by')).toBe('id');
    expect(query?.get('sort_order')).toBe('desc');
    expect(query?.has('auditable_type')).toBe(false);
  });

  it('sends the chosen dates in the backend format', async () => {
    permissions = ['audit-log.list'];
    const queries: URLSearchParams[] = [];
    server.use(
      http.get(apiUrl('/admin/audit-log/list'), ({ request }) => {
        queries.push(new URL(request.url).searchParams);
        return HttpResponse.json(page([entry]));
      }),
    );

    renderBrowser();
    await screen.findByText('types.skill #4');
    fireEvent.click(screen.getByRole('button', { name: 'show' }));
    fireEvent.change(screen.getByLabelText('fromDate'), { target: { value: '2026-10-01' } });
    fireEvent.click(screen.getByRole('button', { name: /apply/i }));

    await waitFor(() => expect(queries.at(-1)?.get('from_date')).toBe('01/10/2026'));
    expect(queries.at(-1)?.get('page')).toBe('1');
  });

  it('offers the "by" filter only to admins who may list admins', async () => {
    permissions = ['audit-log.list', 'admin-mst.list'];
    server.use(
      http.get(apiUrl('/admin/audit-log/list'), () => HttpResponse.json(page([]))),
      http.get(apiUrl('/admin/admin-mst/list'), () =>
        HttpResponse.json(page([{ id: 1, user_name: 'root' }])),
      ),
    );

    renderBrowser();
    fireEvent.click(await screen.findByRole('button', { name: 'show' }));

    await screen.findByText('actor', { selector: 'label' });
  });
});
