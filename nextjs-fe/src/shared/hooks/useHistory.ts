/**
 * useHistory Hook
 * Hook for fetching and managing history data
 */

import { useState, useCallback } from 'react';
import { useTranslations } from 'next-intl';
import { apiClient } from '@/shared/api/client';
import type { PaginatedResponse, UseHistoryOptions, UseHistoryReturn } from '@/shared/types/api';
import type { BaseHistory, HistoryFilterOptions, HistoryDiff } from '@/shared/types/models/history';
import { PAGINATION } from '@/shared/config/constant';
import { getPaginationInfo } from '@/shared/utils/pagination';

export function useHistory<T extends BaseHistory = BaseHistory>({
  baseUrl,
  recordId,
}: UseHistoryOptions): UseHistoryReturn<T> {
  const t = useTranslations('common');
  const [history, setHistory] = useState<T[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<Error | null>(null);
  const [pagination, setPagination] = useState({
    page: PAGINATION.DEFAULT_PAGE as number,
    perPage: PAGINATION.DEFAULT_PER_PAGE as number,
    total: 0,
  });

  const fetchHistory = useCallback(
    async (filters?: HistoryFilterOptions) => {
      setIsLoading(true);
      setError(null);

      try {
        const params = {
          record_id: recordId,
          page: filters?.page || pagination.page,
          per_page: filters?.per_page || pagination.perPage,
          action: filters?.action,
          changed_by: filters?.changed_by,
          from_date: filters?.from_date,
          to_date: filters?.to_date,
        };

        const response = await apiClient.get<PaginatedResponse<T>>(
          `${baseUrl}/list`,
          { params }
        );

        setHistory(response.data.data);
        const paginationInfo = getPaginationInfo(response.data);
        setPagination({
          page: paginationInfo.currentPage,
          perPage: paginationInfo.perPage,
          total: paginationInfo.total,
        });
      } catch (err) {
        setError(err instanceof Error ? err : new Error(t('failedToFetchHistory')));
      } finally {
        setIsLoading(false);
      }
    },
    [baseUrl, recordId, pagination.page, pagination.perPage, t]
  );

  const compareVersions = useCallback((oldVersion: T, newVersion: T): HistoryDiff[] => {
    const diffs: HistoryDiff[] = [];
    const oldValues = oldVersion.old_values || {};
    const newValues = newVersion.new_values || {};

    // Get all unique keys from both versions
    const allKeys = new Set([
      ...Object.keys(oldValues),
      ...Object.keys(newValues),
    ]);

    allKeys.forEach((key) => {
      const oldValue = oldValues[key];
      const newValue = newValues[key];

      // Only add to diffs if values are different
      if (JSON.stringify(oldValue) !== JSON.stringify(newValue)) {
        diffs.push({
          field: key,
          oldValue,
          newValue,
          label: key.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase()),
        });
      }
    });

    return diffs;
  }, []);

  const restoreVersion = useCallback(
    async (historyId: number) => {
      try {
        await apiClient.post(`${baseUrl}/restore/${historyId}`, {});
        // Refresh history after restore
        await fetchHistory();
      } catch (err) {
        throw err instanceof Error ? err : new Error(t('failedToRestoreVersion'));
      }
    },
    [baseUrl, fetchHistory, t]
  );

  return {
    history,
    isLoading,
    error,
    pagination,
    fetchHistory,
    compareVersions,
    restoreVersion,
  };
}
