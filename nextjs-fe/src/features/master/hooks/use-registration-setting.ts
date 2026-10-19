'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/shared/api/client';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { queryKeys } from '@/shared/api/query-keys';

const BASE = API_ENDPOINTS.MASTER.END_USER;

/** Whether visitors may create an account on the public site (ADR-0020), and the switch for it. */
export function useRegistrationSetting() {
  const queryClient = useQueryClient();
  const key = queryKeys.query(`${BASE}/registration`);
  const setting = useQuery({
    queryKey: key,
    queryFn: async () => (await apiClient.get<{ open: boolean }>(`${BASE}/registration`)).data.open,
  });
  const update = useMutation({
    mutationFn: async (open: boolean) => {
      await apiClient.put(`${BASE}/registration-update`, { open });
      return open;
    },
    onSuccess: (open) => queryClient.setQueryData(key, open),
  });

  return { setting, update };
}
