'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient, API_ENDPOINTS, queryKeys } from '@/shared/api';
import { useApiData } from '@/shared/hooks/use-api-data';
import type { SortOrder } from '@/shared/config';
import type { MediaItem } from '@/shared/types/models';

const BASE = API_ENDPOINTS.MEDIA.BASE;

/** Default items per page of a folder (laravel-api MediaConst::PER_PAGE). */
export const MEDIA_PER_PAGE = 50;

export interface MediaListParams {
  folder: string;
  name: string;
  page: number;
  perPage: number;
  sortBy: string;
  sortOrder: SortOrder;
}

/** Direct children of a folder, folders first. */
export function useMediaList({ folder, name, page, perPage, sortBy, sortOrder }: MediaListParams) {
  return useApiData<MediaItem>(BASE, {
    page,
    per_page: perPage,
    filters: name ? { folder, name } : { folder },
    sort_by: sortBy,
    sort_order: sortOrder,
  });
}

/** Folder, rename / move and delete calls; each refreshes every cached folder listing. */
export function useMediaActions() {
  const queryClient = useQueryClient();
  const refresh = () => queryClient.invalidateQueries({ queryKey: queryKeys.resource(BASE) });

  const createFolder = useMutation({
    mutationFn: (input: { folder: string; name: string }) =>
      apiClient.post<number>(`${BASE}/store-folder`, input),
    onSuccess: refresh,
  });
  const update = useMutation({
    mutationFn: ({ id, ...input }: { id: number; name?: string; folder?: string }) =>
      apiClient.put<number>(`${BASE}/update/${id}`, input),
    onSuccess: refresh,
  });
  const remove = useMutation({
    mutationFn: (ids: number[]) => apiClient.post(`${BASE}/delete`, { ids }),
    onSuccess: refresh,
  });

  /** Asks for a short-lived download link and follows it (the file is sent as an attachment). */
  const download = async (id: number) => {
    const { data } = await apiClient.get<{ url: string }>(`${BASE}/download-url/${id}`);
    window.location.assign(data.url);
  };

  return { createFolder, update, remove, download, refresh };
}
