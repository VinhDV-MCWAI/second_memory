import { describe, expect, it, vi } from 'vitest';
import { act, renderHook } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { useHistory } from './useHistory';
import type { BaseHistory } from '@/shared/types/models/history';
import { apiUrl, envelope, server } from '@/test/server';

vi.mock('next-intl', () => ({
  useTranslations: () => (key: string) => key,
}));

const BASE = '/admin/banner-mgmt-hist';

const version = (values: Partial<BaseHistory>): BaseHistory => ({
  id: 1,
  action: 'update',
  changed_by: 1,
  changed_at: '2026-10-01',
  ...values,
});

describe('useHistory', () => {
  it('lists history for the record and stores the pagination', async () => {
    let params: URLSearchParams | undefined;
    server.use(
      http.get(apiUrl(`${BASE}/list`), ({ request }) => {
        params = new URL(request.url).searchParams;
        return HttpResponse.json(
          envelope({ data: [{ id: 7 }], current_page: 2, per_page: 5, total: 11 }),
        );
      }),
    );
    const { result } = renderHook(() => useHistory({ baseUrl: BASE, recordId: 3 }));

    await act(async () => {
      await result.current.fetchHistory({ page: 2, per_page: 5, action: 'update' });
    });

    expect(params?.get('record_id')).toBe('3');
    expect(params?.get('page')).toBe('2');
    expect(params?.get('action')).toBe('update');
    expect(result.current.history).toEqual([{ id: 7 }]);
    expect(result.current.pagination).toEqual({ page: 2, perPage: 5, total: 11 });
    expect(result.current.isLoading).toBe(false);
  });

  it('exposes request failures as error state', async () => {
    server.use(http.get(apiUrl(`${BASE}/list`), () => new HttpResponse(null, { status: 500 })));
    const { result } = renderHook(() => useHistory({ baseUrl: BASE, recordId: 3 }));

    await act(async () => {
      await result.current.fetchHistory();
    });

    expect(result.current.error).toBeInstanceOf(Error);
    expect(result.current.history).toEqual([]);
  });

  it('diffs changed fields with readable labels', () => {
    const { result } = renderHook(() => useHistory({ baseUrl: BASE, recordId: 3 }));

    const diffs = result.current.compareVersions(
      version({ old_values: { rank_order: 1, is_display: true, title: 'A' } }),
      version({ new_values: { rank_order: 2, is_display: true, title: 'A' } }),
    );

    expect(diffs).toEqual([{ field: 'rank_order', oldValue: 1, newValue: 2, label: 'Rank Order' }]);
  });

  it('posts a restore and reloads the list', async () => {
    const calls: string[] = [];
    server.use(
      http.post(apiUrl(`${BASE}/restore/4`), () => {
        calls.push('restore');
        return HttpResponse.json(envelope(null));
      }),
      http.get(apiUrl(`${BASE}/list`), () => {
        calls.push('list');
        return HttpResponse.json(envelope({ data: [], current_page: 1, per_page: 10, total: 0 }));
      }),
    );
    const { result } = renderHook(() => useHistory({ baseUrl: BASE, recordId: 3 }));

    await act(async () => {
      await result.current.restoreVersion(4);
    });

    expect(calls).toEqual(['restore', 'list']);
  });
});
