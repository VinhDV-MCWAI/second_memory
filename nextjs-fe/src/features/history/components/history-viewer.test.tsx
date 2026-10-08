import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { HistoryViewer } from './history-viewer';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

vi.mock('next-intl', () => {
  const t = (key: string, params?: Record<string, string>) =>
    params ? `${key}:${JSON.stringify(params)}` : key;
  return { useTranslations: () => t };
});

const page = (data: unknown[]) =>
  envelope({ data, current_page: 1, last_page: 1, per_page: 15, total: data.length });

describe('HistoryViewer', () => {
  it('asks the audit log for one record and shows each change', async () => {
    let query: URLSearchParams | undefined;
    server.use(
      http.get(apiUrl('/admin/audit-log/list'), ({ request }) => {
        query = new URL(request.url).searchParams;
        return HttpResponse.json(
          page([
            {
              id: 2,
              auditable_type: 'admin',
              auditable_id: 7,
              event: 'updated',
              old_values: { first_name: 'Ada' },
              new_values: { first_name: 'Grace' },
              admin_mst_id: 1,
              actor_user_name: 'root',
              ip_address: null,
              created_at: '2026-10-07T10:00:00+00:00',
            },
            {
              id: 1,
              auditable_type: 'admin',
              auditable_id: 7,
              event: 'created',
              old_values: null,
              new_values: { first_name: 'Ada' },
              admin_mst_id: 1,
              actor_user_name: null,
              ip_address: null,
              created_at: '2026-10-06T10:00:00+00:00',
            },
          ]),
        );
      }),
    );
    const { Wrapper } = createQueryWrapper();

    render(
      <Wrapper>
        <HistoryViewer auditableType="admin" recordId={7} />
      </Wrapper>,
    );

    expect(await screen.findByText('events.updated')).toBeTruthy();
    expect(query?.get('auditable_type')).toBe('admin');
    expect(query?.get('auditable_id')).toBe('7');
    expect(query?.get('sort_order')).toBe('desc');
    expect(screen.getByText('events.created')).toBeTruthy();
    expect(screen.getByText('Grace')).toBeTruthy();
    expect(screen.getByText('by:{"name":"root"}')).toBeTruthy();
    expect(screen.getByText('by:{"name":"#1"}')).toBeTruthy();
  });

  it('says so when nothing was recorded', async () => {
    server.use(http.get(apiUrl('/admin/audit-log/list'), () => HttpResponse.json(page([]))));
    const { Wrapper } = createQueryWrapper();

    render(
      <Wrapper>
        <HistoryViewer auditableType="admin" recordId={7} />
      </Wrapper>,
    );

    expect(await screen.findByText('noHistory')).toBeTruthy();
  });
});
