'use client';

import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/shared/api/client';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { queryKeys } from '@/shared/api/query-keys';
import { SEARCH_QUERY_MIN } from '@/shared/config';
import type { DashboardSummary, SearchResult } from '@/shared/types/models';

/** Category and article counts for the dashboard. */
export function useDashboardSummary() {
  return useQuery({
    queryKey: queryKeys.query(API_ENDPOINTS.CONTENT.DASHBOARD),
    queryFn: async () =>
      (await apiClient.get<DashboardSummary>(API_ENDPOINTS.CONTENT.DASHBOARD)).data,
  });
}

/** The admin search; runs once the text is long enough. */
export function useSearch(q: string) {
  const text = q.trim();
  return useQuery({
    queryKey: queryKeys.query(API_ENDPOINTS.CONTENT.SEARCH, { q: text }),
    queryFn: async () =>
      (await apiClient.get<SearchResult>(API_ENDPOINTS.CONTENT.SEARCH, { params: { q: text } }))
        .data,
    enabled: text.length >= SEARCH_QUERY_MIN,
  });
}
