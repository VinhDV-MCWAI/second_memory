'use client';

import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/shared/api/client';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { queryKeys } from '@/shared/api/query-keys';
import { SEARCH_QUERY_MIN } from '@/shared/config';
import type { DashboardSummary, SearchResult } from '@/shared/types/models';

/** Skill Ledger counts for the dashboard (REQ-002 US-7). */
export function useDashboardSummary() {
  return useQuery({
    queryKey: queryKeys.query(API_ENDPOINTS.LEDGER.DASHBOARD),
    queryFn: async () =>
      (await apiClient.get<DashboardSummary>(API_ENDPOINTS.LEDGER.DASHBOARD)).data,
  });
}

/** One search over skills, goals and evidence; runs once the text is long enough (REQ-002 US-5). */
export function useLedgerSearch(q: string) {
  const text = q.trim();
  return useQuery({
    queryKey: queryKeys.query(API_ENDPOINTS.LEDGER.SEARCH, { q: text }),
    queryFn: async () =>
      (await apiClient.get<SearchResult>(API_ENDPOINTS.LEDGER.SEARCH, { params: { q: text } }))
        .data,
    enabled: text.length >= SEARCH_QUERY_MIN,
  });
}
