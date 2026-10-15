'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient, API_ENDPOINTS, queryKeys } from '@/shared/api';
import { useApiData } from '@/shared/hooks/use-api-data';
import type { SortOrder } from '@/shared/config';
import type { MediaItem, UnfinishedUpload } from '@/shared/types/models';
import { awaitingMediaInfo, MEDIA_INFO_POLL_MS } from '../lib/media-info';

const BASE = API_ENDPOINTS.MEDIA.BASE;

const UNFINISHED = `${API_ENDPOINTS.MEDIA.MULTIPART}/unfinished`;

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
    // Thumbnails and durations arrive from a background job shortly after an upload (MEDIA-11)
    refetchInterval: (rows) => (awaitingMediaInfo(rows) ? MEDIA_INFO_POLL_MS : false),
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

/** Your large uploads that stopped before the end (reload, network), to resume or discard. */
export function useUnfinishedUploads(enabled: boolean) {
  return useQuery({
    queryKey: queryKeys.resource(UNFINISHED),
    queryFn: async () => (await apiClient.get<UnfinishedUpload[]>(UNFINISHED)).data,
    enabled,
  });
}

/** A preview link lives an hour (MediaConst::PREVIEW_URL_TTL_SECONDS); ask again a little before. */
const PREVIEW_STALE_MS = 50 * 60 * 1000;

/**
 * Link that shows a file in the browser (`download-url/{id}?inline=1`). Keyed apart from the
 * listing, so refreshing the folder does not reload a video that is playing.
 */
export function usePreviewUrl(id: number | null) {
  const endpoint = `${BASE}/download-url/${id}`;
  return useQuery({
    queryKey: queryKeys.query(endpoint, { inline: 1 }),
    queryFn: async () =>
      (await apiClient.get<{ url: string }>(endpoint, { params: { inline: 1 } })).data.url,
    enabled: id !== null,
    staleTime: PREVIEW_STALE_MS,
    gcTime: PREVIEW_STALE_MS,
  });
}
