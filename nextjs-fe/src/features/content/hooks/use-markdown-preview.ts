'use client';

import { useMutation } from '@tanstack/react-query';
import { apiClient } from '@/shared/api/client';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import type { MarkdownPreview } from '@/shared/types/models';

/** Renders Markdown on the server exactly as the public page will (ADR-0022); nothing is saved. */
export function useMarkdownPreview() {
  return useMutation({
    mutationFn: async (body: string) =>
      (await apiClient.post<MarkdownPreview>(API_ENDPOINTS.CONTENT.ARTICLE_PREVIEW, { body })).data,
  });
}
