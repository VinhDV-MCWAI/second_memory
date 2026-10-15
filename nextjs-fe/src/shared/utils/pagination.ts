import { PAGINATION } from '@/shared/config';
import type { PaginatedResponse } from '@/shared/types/api';

/**
 * Pagination info of a `{resource}/list` response, read from its `meta` block.
 * Defaults apply before the first response; `from` / `to` are null on an empty page.
 */
export const getPaginationInfo = (data: PaginatedResponse<unknown> | undefined) => {
  const meta = data?.meta;

  return {
    currentPage: meta?.current_page ?? PAGINATION.DEFAULT_PAGE,
    lastPage: meta?.last_page ?? PAGINATION.DEFAULT_TOTAL_PAGES,
    total: meta?.total ?? PAGINATION.DEFAULT_TOTAL,
    perPage: meta?.per_page ?? PAGINATION.DEFAULT_PER_PAGE,
    from: meta?.from ?? PAGINATION.DEFAULT_FROM,
    to: meta?.to ?? PAGINATION.DEFAULT_TO,
  };
};
