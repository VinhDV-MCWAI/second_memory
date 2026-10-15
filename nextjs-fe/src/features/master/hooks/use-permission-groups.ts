'use client';

import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/shared/api/client';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { queryKeys } from '@/shared/api/query-keys';
import type { PermissionGroup } from '@/shared/types/models';

/** Every permission, grouped by feature (ADR-0013); changes only when the API gains a route. */
export function usePermissionGroups() {
  return useQuery({
    queryKey: queryKeys.query(API_ENDPOINTS.MASTER.PERMISSION),
    queryFn: async () =>
      (await apiClient.get<PermissionGroup[]>(API_ENDPOINTS.MASTER.PERMISSION)).data,
  });
}
