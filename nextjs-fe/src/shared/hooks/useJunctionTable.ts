'use client';

import { useState, useCallback, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { apiClient } from '@/shared/api/client';
import { notification } from '../utils';
import { PAGINATION } from '@/shared/config/constant';
import type { UseJunctionTableReturn, PaginatedResponse } from '@/shared/types/api';

/**
 * Hook for managing junction table relationships
 * Handles fetching, selecting, and updating many-to-many relationships
 */
export function useJunctionTable<T = unknown>(
  junctionEndpoint: string,
  allItemsEndpoint: string,
  parentIdKey: string,
  childIdKey: string,
  parentId: number
): UseJunctionTableReturn<T> {
  const t = useTranslations('common');
  const [allItems, setAllItems] = useState<T[]>([]);
  const [assignedIds, setAssignedIds] = useState<number[]>([]);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  /**
   * Fetch all available items and assigned relationships
   */
  const fetchData = useCallback(async () => {
    try {
      setLoading(true);

      // Fetch all available items (e.g., all roles)
      const allItemsResponse = await apiClient.get<PaginatedResponse<T> | T[]>(`${allItemsEndpoint}/list`, {
        params: {
          per_page: PAGINATION.MAX_PER_PAGE,
        }
      });
      const data = allItemsResponse.data;
      setAllItems(Array.isArray(data) ? data : (data as PaginatedResponse<T>).data || []);

      // Fetch assigned relationships
      const assignedResponse = await apiClient.get<PaginatedResponse<Record<string, number>> | Record<string, number>[]>(`${junctionEndpoint}/list`, {
        params: {
          [parentIdKey]: parentId,
          per_page: PAGINATION.MAX_PER_PAGE,
        }
      });

      const assignedData = assignedResponse.data;
      const assigned = Array.isArray(assignedData) ? assignedData : (assignedData as PaginatedResponse<Record<string, number>>).data || [];
      const assignedItemIds = assigned.map((item: Record<string, number>) => item[childIdKey]);

      setAssignedIds(assignedItemIds);
      setSelectedIds(assignedItemIds);
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } };
      const message = err.response?.data?.message || t('failedToLoadData');
      notification.error(message);
    } finally {
      setLoading(false);
    }
  }, [junctionEndpoint, allItemsEndpoint, parentIdKey, childIdKey, parentId, t]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  /**
   * Toggle selection of an item
   */
  const toggleSelection = useCallback((id: number) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
    );
  }, []);

  /**
   * Save changes (insert new and delete removed relationships)
   */
  const save = useCallback(async () => {
    try {
      setSaving(true);

      // Calculate what to delete and insert
      const toDelete = assignedIds
        .filter((id) => !selectedIds.includes(id))
        .map((id) => ({
          [parentIdKey]: parentId,
          [childIdKey]: id,
        }));

      const toInsert = selectedIds
        .filter((id) => !assignedIds.includes(id))
        .map((id) => ({
          [parentIdKey]: parentId,
          [childIdKey]: id,
        }));

      if (toDelete.length === 0 && toInsert.length === 0) {
        notification.success(t('noChangesToSave'));
        return;
      }

      const updateData: Record<string, unknown> = {
        [parentIdKey]: parentId,
      };

      if (toDelete.length > 0) updateData.delete = toDelete;
      if (toInsert.length > 0) updateData.insert = toInsert;

      await apiClient.put(`${junctionEndpoint}/update`, updateData);

      setAssignedIds(selectedIds);
      notification.success(t('relationshipsUpdatedSuccessfully'));
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } };
      const message = err.response?.data?.message || t('failedToUpdate');
      notification.error(message);
      throw error;
    } finally {
      setSaving(false);
    }
  }, [
    junctionEndpoint,
    parentIdKey,
    childIdKey,
    parentId,
    assignedIds,
    selectedIds,
    t,
  ]);

  return {
    allItems,
    assignedIds,
    selectedIds,
    loading,
    saving,
    setSelectedIds,
    toggleSelection,
    save,
    refetch: fetchData,
  };
}
