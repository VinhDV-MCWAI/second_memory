'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient, API_ENDPOINTS, queryKeys } from '@/shared/api';
import { useApiData } from '@/shared/hooks/use-api-data';
import type { MediaItem, TrashItem } from '@/shared/types/models';

const BASE = API_ENDPOINTS.MEDIA.BASE;
const TRASH = `${BASE}/trash`;

/** Days a deleted file stays in the trash (laravel-api MediaConst::TRASH_DAYS). */
export const TRASH_DAYS = 30;

/** Deleted files still kept, most recently deleted first. */
export function useTrashList(params: { name: string; page: number; perPage: number }) {
  return useApiData<TrashItem>(TRASH, {
    page: params.page,
    per_page: params.perPage,
    filters: params.name ? { name: params.name } : {},
  });
}

/** Restore, delete for good, empty; each refreshes the trash and the folder listings. */
export function useTrashActions() {
  const queryClient = useQueryClient();
  const refresh = () =>
    Promise.all([
      queryClient.invalidateQueries({ queryKey: queryKeys.resource(TRASH) }),
      queryClient.invalidateQueries({ queryKey: queryKeys.resource(BASE) }),
    ]);

  const restore = useMutation({
    mutationFn: async (id: number) =>
      (await apiClient.post<{ data: MediaItem }>(`${TRASH}/restore/${id}`)).data.data,
    onSuccess: refresh,
  });
  const purge = useMutation({
    mutationFn: (ids: number[]) => apiClient.post<number>(`${TRASH}/delete`, { ids }),
    onSuccess: refresh,
  });
  const empty = useMutation({
    mutationFn: () => apiClient.post<number>(`${TRASH}/empty`),
    onSuccess: refresh,
  });

  return { restore, purge, empty };
}
