import { describe, expect, it } from 'vitest';
import { getPaginationInfo } from './pagination';
import { PAGINATION } from '@/shared/config';
import type { PaginatedResponse } from '@/shared/types/api';

const page = (meta: Partial<PaginatedResponse<unknown>['meta']>): PaginatedResponse<unknown> => ({
  data: [],
  links: { first: null, last: null, prev: null, next: null },
  meta: {
    current_page: 1,
    from: null,
    last_page: 1,
    links: [],
    path: '/api/admin/tag/list',
    per_page: 15,
    to: null,
    total: 0,
    ...meta,
  },
});

describe('getPaginationInfo', () => {
  it('falls back to defaults without data', () => {
    expect(getPaginationInfo(undefined)).toEqual({
      currentPage: PAGINATION.DEFAULT_PAGE,
      lastPage: PAGINATION.DEFAULT_TOTAL_PAGES,
      total: PAGINATION.DEFAULT_TOTAL,
      perPage: PAGINATION.DEFAULT_PER_PAGE,
      from: PAGINATION.DEFAULT_FROM,
      to: PAGINATION.DEFAULT_TO,
    });
  });

  it('reads the meta block of a Laravel resource collection', () => {
    const info = getPaginationInfo(
      page({ current_page: 2, last_page: 5, total: 42, per_page: 10, from: 11, to: 20 }),
    );
    expect(info).toEqual({ currentPage: 2, lastPage: 5, total: 42, perPage: 10, from: 11, to: 20 });
  });

  it('keeps a total of 0 and maps the null from / to of an empty page to the defaults', () => {
    expect(getPaginationInfo(page({}))).toMatchObject({
      total: 0,
      from: PAGINATION.DEFAULT_FROM,
      to: PAGINATION.DEFAULT_TO,
    });
  });
});
