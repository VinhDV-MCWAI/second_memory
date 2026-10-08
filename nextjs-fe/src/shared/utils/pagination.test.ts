import { describe, expect, it } from 'vitest';
import { getPaginationInfo } from './pagination';
import { PAGINATION } from '@/shared/config';

describe('getPaginationInfo', () => {
  it('falls back to defaults without data', () => {
    expect(getPaginationInfo(undefined)).toMatchObject({
      currentPage: PAGINATION.DEFAULT_PAGE,
      perPage: PAGINATION.DEFAULT_PER_PAGE,
    });
  });

  it('reads the meta block of a Laravel resource collection', () => {
    const info = getPaginationInfo({
      meta: { current_page: 2, last_page: 5, total: 42, per_page: 10, from: 11, to: 20 },
    });
    expect(info).toEqual({ currentPage: 2, lastPage: 5, total: 42, perPage: 10, from: 11, to: 20 });
  });

  it('reads a flat paginator', () => {
    const info = getPaginationInfo({ current_page: 3, last_page: 4, total: 31, per_page: 10 });
    expect(info).toMatchObject({ currentPage: 3, lastPage: 4, total: 31, perPage: 10 });
  });
});
