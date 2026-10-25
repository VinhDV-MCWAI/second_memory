'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslations } from 'next-intl';
import { apiClient } from '@/shared/api/client';
import { queryKeys } from '@/shared/api/query-keys';
import { getApiErrorMessage, notification } from '@/shared/utils';
import type { DropTarget } from '../lib/category-tree';

export type MoveDirection = 'up' | 'down';

/** One step among the siblings (↑ ↓), or a drop in the category tree (ADR-0023). */
export type MoveBody = { direction: MoveDirection } | DropTarget;

/**
 * The ↑ ↓ buttons (ADR-0022) and the category tree's drag and drop: `PUT {endpoint}/move/{id}`,
 * then the lists of that endpoint reload.
 */
export function useMoveRow(endpoint: string) {
  const queryClient = useQueryClient();
  const t = useTranslations('common');

  return useMutation({
    mutationFn: async ({ id, ...body }: { id: number } & MoveBody) => {
      await apiClient.put(`${endpoint}/move/${id}`, body);
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKeys.resource(endpoint) }),
    onError: (error: unknown) =>
      notification.error(getApiErrorMessage(error) ?? t('failedToUpdate')),
  });
}
