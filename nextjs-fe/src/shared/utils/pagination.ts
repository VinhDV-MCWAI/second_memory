
import { PAGINATION } from '@/shared/config/constant';
import type { PaginationSource } from '@/shared/types/api';

/**
 * Extracts standardized pagination information from various API response structures.
 * Supports:
 * 1. Nested `meta` object (standard back-end Resource Collection)
 * 2. Flat structure (standard back-end Paginator)
 * 3. CamelCase variations
 */
export const getPaginationInfo = (data: PaginationSource | undefined) => {
  if (!data) {
    return {
      currentPage: PAGINATION.DEFAULT_PAGE,
      lastPage: PAGINATION.DEFAULT_TOTAL_PAGES,
      total: PAGINATION.DEFAULT_TOTAL,
      perPage: PAGINATION.DEFAULT_PER_PAGE,
      from: PAGINATION.DEFAULT_FROM,
      to: PAGINATION.DEFAULT_TO,
    };
  }

  // Check for nested meta object (back-end Resource Collection)
  if (data.meta) {
    return {
      currentPage: data.meta.current_page || PAGINATION.DEFAULT_PAGE,
      lastPage: data.meta.last_page || PAGINATION.DEFAULT_TOTAL_PAGES,
      total: data.meta.total || PAGINATION.DEFAULT_TOTAL,
      perPage: data.meta.per_page || PAGINATION.DEFAULT_PER_PAGE,
      from: data.meta.from || PAGINATION.DEFAULT_FROM,
      to: data.meta.to || PAGINATION.DEFAULT_TO,
    };
  }

  // Flat structure (back-end Paginator default)
  return {
    currentPage: data.current_page || data.currentPage || PAGINATION.DEFAULT_PAGE,
    lastPage: data.last_page || data.lastPage || PAGINATION.DEFAULT_TOTAL_PAGES,
    total: data.total || PAGINATION.DEFAULT_TOTAL,
    perPage: data.per_page || data.perPage || PAGINATION.DEFAULT_PER_PAGE,
    from: data.from || PAGINATION.DEFAULT_FROM,
    to: data.to || PAGINATION.DEFAULT_TO,
  };
};
