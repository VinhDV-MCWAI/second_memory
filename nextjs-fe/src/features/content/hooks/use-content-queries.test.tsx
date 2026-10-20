import { describe, expect, it } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { useDashboardSummary, useSearch } from './use-content-queries';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

describe('content queries', () => {
  it('reads the dashboard counts', async () => {
    const counts = { categories: 3, articles: 5, public_articles: 4 };
    server.use(
      http.get(apiUrl('/admin/dashboard/summary'), () => HttpResponse.json(envelope(counts))),
    );

    const { Wrapper } = createQueryWrapper();
    const { result } = renderHook(() => useDashboardSummary(), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.data).toEqual(counts));
  });

  it('searches the trimmed text, and only from the shortest length on', async () => {
    const queries: (string | null)[] = [];
    server.use(
      http.get(apiUrl('/admin/search'), ({ request }) => {
        queries.push(new URL(request.url).searchParams.get('q'));
        return HttpResponse.json(envelope({ match: 'exact', files: [] }));
      }),
    );

    const { Wrapper } = createQueryWrapper();
    const short = renderHook(() => useSearch(' a '), { wrapper: Wrapper });
    const long = renderHook(() => useSearch(' plan '), { wrapper: Wrapper });

    await waitFor(() => expect(long.result.current.data).toEqual({ match: 'exact', files: [] }));
    expect(short.result.current.fetchStatus).toBe('idle');
    expect(queries).toEqual(['plan']);
  });
});
