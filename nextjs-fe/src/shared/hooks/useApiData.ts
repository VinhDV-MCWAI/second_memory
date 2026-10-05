'use client';

import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/shared/api/client';
import type {
  PaginatedResponse,
  UseApiDataOptions,
  UseApiDataReturn,
  FilterValue,
} from '@/shared/types/api';
import { PAGINATION, SORT_ORDER } from '@/shared/config/constant';
import { getPaginationInfo } from '@/shared/utils/pagination';

/**
 * Generic hook for fetching paginated data from API using TanStack Query
 * Supports pagination, filtering, sorting, and date range
 * 
 * Benefits over old implementation:
 * - Automatic caching and background refetching
 * - Request deduplication
 * - Better loading states (initial load vs refetch)
 * - Automatic retry on failure
 */

const EMPTY_ARRAY: never[] = [];
export function useApiData<T>(
  endpoint: string,
  options: UseApiDataOptions = {}
): UseApiDataReturn<T> {
  const {
    page = PAGINATION.DEFAULT_PAGE,
    per_page = PAGINATION.DEFAULT_PER_PAGE,
    filters = {},
    sort_by,
    sort_order = SORT_ORDER.ASC,
    from_date,
    to_date,
    enabled = true,
    staleTime,
  } = options;

  // Build query key for caching
  const queryKey = [
    endpoint,
    {
      page,
      per_page,
      filters,
      sort_by,
      sort_order,
      from_date,
      to_date,
    },
  ];

  // Fetch function
  const fetchData = async (): Promise<PaginatedResponse<T>> => {
    const params: Record<string, FilterValue> = {
      page,
      per_page,
      ...filters,
    };

    if (sort_by) params.sort_by = sort_by;
    if (sort_order) params.sort_order = sort_order;
    if (from_date) params.from_date = from_date;
    if (to_date) params.to_date = to_date;

    const response = await apiClient.get<PaginatedResponse<T>>(
      `${endpoint}/list`,
      { params }
    );

    return response.data;
  };

  // Use TanStack Query
  const query = useQuery({
    queryKey,
    queryFn: fetchData,
    enabled,
    staleTime,
    refetchOnMount: options.refetchOnMount,
    // Keep previous data while fetching new page
    placeholderData: (previousData) => previousData,
  });

  // Extract pagination info

  const responseData = query.data;



  const paginationInfo = getPaginationInfo(responseData);

  return {
    data: responseData?.data || EMPTY_ARRAY,
    loading: query.isLoading,
    error: query.error as Error | null,
    pagination: paginationInfo,
    refetch: () => {
      query.refetch();
    },
    isRefetching: query.isRefetching,
  };
}
