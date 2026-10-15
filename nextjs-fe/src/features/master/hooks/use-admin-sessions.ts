'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/shared/api/client';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { queryKeys } from '@/shared/api/query-keys';
import type { AdminSession } from '@/shared/types/models';

const BASE = API_ENDPOINTS.MASTER.ADMIN_SESSION;

/** The devices the signed-in admin is signed in on, most recently used first (AUTH-04). */
export function useAdminSessions() {
  return useQuery({
    queryKey: queryKeys.query(BASE),
    queryFn: async () => (await apiClient.get<{ data: AdminSession[] }>(`${BASE}/list`)).data.data,
  });
}

/** Sign out some devices, or every device but this one; both return how many were signed out. */
export function useAdminSessionActions() {
  const queryClient = useQueryClient();
  const refresh = () => queryClient.invalidateQueries({ queryKey: queryKeys.resource(BASE) });

  const end = useMutation({
    mutationFn: async (ids: number[]) =>
      (await apiClient.post<number>(`${BASE}/delete`, { ids })).data,
    onSuccess: refresh,
  });
  const endOthers = useMutation({
    mutationFn: async () => (await apiClient.post<number>(`${BASE}/delete-others`)).data,
    onSuccess: refresh,
  });

  return { end, endOthers };
}
