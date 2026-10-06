import { describe, expect, it } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { useApiData } from './useApiData';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

const ENDPOINT = '/admin/banner-mgmt';

const listResponse = (ids: number[], page = 1) =>
  envelope({
    data: ids.map((id) => ({ id })),
    meta: { current_page: page, last_page: 3, total: 25, per_page: 10, from: 1, to: 10 },
  });

describe('useApiData', () => {
  it('requests {endpoint}/list with paging, sorting, dates and filters', async () => {
    let params: URLSearchParams | undefined;
    server.use(
      http.get(apiUrl(`${ENDPOINT}/list`), ({ request }) => {
        params = new URL(request.url).searchParams;
        return HttpResponse.json(listResponse([1, 2], 2));
      }),
    );

    const { Wrapper } = createQueryWrapper();
    const { result } = renderHook(
      () =>
        useApiData<{ id: number }>(ENDPOINT, {
          page: 2,
          per_page: 10,
          sort_by: 'title',
          sort_order: 'desc',
          from_date: '01/01/2026',
          filters: { status: 1 },
        }),
      { wrapper: Wrapper },
    );

    await waitFor(() => expect(result.current.loading).toBe(false));

    expect(Object.fromEntries(params!)).toEqual({
      page: '2',
      per_page: '10',
      sort_by: 'title',
      sort_order: 'desc',
      from_date: '01/01/2026',
      status: '1',
    });
    expect(result.current.data).toEqual([{ id: 1 }, { id: 2 }]);
    expect(result.current.pagination).toMatchObject({ currentPage: 2, lastPage: 3, total: 25 });
    expect(result.current.error).toBeNull();
  });

  it('exposes request errors', async () => {
    server.use(http.get(apiUrl(`${ENDPOINT}/list`), () => new HttpResponse(null, { status: 500 })));

    const { Wrapper } = createQueryWrapper();
    const { result } = renderHook(() => useApiData(ENDPOINT), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.error).not.toBeNull());
    expect(result.current.data).toEqual([]);
  });

  it('does not fetch when disabled', async () => {
    let requested = false;
    server.use(
      http.get(apiUrl(`${ENDPOINT}/list`), () => {
        requested = true;
        return HttpResponse.json(listResponse([]));
      }),
    );

    const { Wrapper } = createQueryWrapper();
    renderHook(() => useApiData(ENDPOINT, { enabled: false }), { wrapper: Wrapper });

    await new Promise((resolve) => setTimeout(resolve, 50));
    expect(requested).toBe(false);
  });
});
