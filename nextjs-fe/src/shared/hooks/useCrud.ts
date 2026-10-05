'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/shared/api/client';
import { notification } from '../utils';
import { AxiosError } from 'axios';
import { useTranslations } from 'next-intl';
import type { UseCrudReturn, UseCrudOptions } from '@/shared/types/api';

/**
 * Generic CRUD operations hook using TanStack Query mutations
 * Handles create, update, delete with loading states and notifications
 * 
 * Benefits over old implementation:
 * - Automatic query invalidation after mutations
 * - Better error handling
 * - Optimistic updates support (can be added)
 * - Mutation state tracking
 * - i18n support for messages
 */
export function useCrud<T>(
  endpoint: string,
  options: UseCrudOptions = {}
): UseCrudReturn<T> {
  const queryClient = useQueryClient();
  const t = useTranslations('common');
  const { invalidateKeys = [endpoint], messages = {} } = options;

  /**
   * Create mutation
   */
  const createMutation = useMutation({
    mutationFn: async (data: Record<string, unknown>): Promise<number> => {
      const response = await apiClient.post<number>(`${endpoint}/store`, data);
      return response.data;
    },
    onSuccess: () => {
      notification.success(messages.create || t('createdSuccessfully'));
      invalidateKeys.forEach((key) => {
        queryClient.invalidateQueries({ queryKey: [key] });
      });
    },
    onError: (err: AxiosError<{ message?: string }>) => {
      if (err.response?.status !== 422) {
        const message = err.response?.data?.message || t('failedToCreate');
        notification.error(message);
      }
    },
  });

  /**
   * Update mutation
   */
  const updateMutation = useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Record<string, unknown> }): Promise<number> => {
      const response = await apiClient.put<number>(`${endpoint}/update/${id}`, {
        id,
        ...data,
      });
      return response.data;
    },
    onSuccess: () => {
      notification.success(messages.update || t('updatedSuccessfully'));
      invalidateKeys.forEach((key) => {
        queryClient.invalidateQueries({ queryKey: [key] });
      });
    },
    onError: (err: AxiosError<{ message?: string }>) => {
      if (err.response?.status !== 422) {
        const message = err.response?.data?.message || t('failedToUpdate');
        notification.error(message);
      }
    },
  });

  /**
   * Delete mutation
   */
  const deleteMutation = useMutation({
    mutationFn: async (ids: number[]) => {
      /*
       * Refactored to support Batch Delete via POST Payload
       * Sends { ids: number[] } to the {endpoint}/delete endpoint
       */
      await apiClient.post(`${endpoint}/delete`, { ids });
    },
    onSuccess: (_, ids) => {
      const defaultMessage = ids.length > 1
        ? t('deletedItemsSuccessfully', { count: ids.length })
        : t('deletedSuccessfully');
      notification.success(messages.delete || defaultMessage);
      invalidateKeys.forEach((key) => {
        queryClient.invalidateQueries({ queryKey: [key] });
      });
    },
    onError: (err: AxiosError<{ message?: string }>) => {
      const message = err.response?.data?.message || t('failedToDelete');
      notification.error(message);
    },
  });

  // Wrapper functions to maintain API compatibility
  const create = async (data: Record<string, unknown>): Promise<number> => {
    return createMutation.mutateAsync(data);
  };

  const update = async (id: number, data: Record<string, unknown>): Promise<number> => {
    return updateMutation.mutateAsync({ id, data });
  };

  const remove = async (ids: number[]): Promise<void> => {
    return deleteMutation.mutateAsync(ids);
  };

  // Combine loading states
  const loading = createMutation.isPending || updateMutation.isPending || deleteMutation.isPending;
  
  // Combine errors (return the first error if any)
  const error = (createMutation.error || updateMutation.error || deleteMutation.error) as Error | null;

  return {
    create,
    update,
    remove,
    loading,
    error,
  };
}
