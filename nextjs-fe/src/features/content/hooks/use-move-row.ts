'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslations } from 'next-intl';
import { apiClient } from '@/shared/api/client';
import { queryKeys } from '@/shared/api/query-keys';
import { getApiErrorMessage, notification } from '@/shared/utils';

export type MoveDirection = 'up' | 'down';

/** The ↑ ↓ buttons (ADR-0022): `PUT {endpoint}/move/{id}`, then the lists of that endpoint reload. */
export function useMoveRow(endpoint: string) {
  const queryClient = useQueryClient();
  const t = useTranslations('common');

  return useMutation({
    mutationFn: async ({ id, direction }: { id: number; direction: MoveDirection }) => {
      await apiClient.put(`${endpoint}/move/${id}`, { direction });
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKeys.resource(endpoint) }),
    onError: (error: unknown) =>
      notification.error(getApiErrorMessage(error) ?? t('failedToUpdate')),
  });
}
