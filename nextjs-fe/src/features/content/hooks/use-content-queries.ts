'use client';

import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/shared/api/client';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { queryKeys } from '@/shared/api/query-keys';
import { SEARCH_QUERY_MIN } from '@/shared/config';
import { useApiData } from '@/shared/hooks/use-api-data';
import type { ContentCategory, DashboardSummary, SearchResult } from '@/shared/types/models';

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

/** Every category fits on one page: a site has a few hundred at most (REQ-005 volume). */
const CATEGORY_TREE_PAGE_SIZE = 1000;

/** The whole category tree (the API sends it in tree order): pickers and the categories screen. */
export function useCategoryTree() {
  return useApiData<ContentCategory>(API_ENDPOINTS.CONTENT.CATEGORY, {
    per_page: CATEGORY_TREE_PAGE_SIZE,
  });
}
