/**
 * File manager constants.
 */

import { PAGINATION } from './constant';

// File manager sort fields
export const FILE_MANAGER_SORT_FIELDS = {
  NAME: 'name',
  DATE: 'date',
  SIZE: 'size',
  TYPE: 'type',
} as const;

// File manager view modes
export const VIEW_MODE = {
  GRID: 'grid',
  LIST: 'list',
} as const;

export type ViewMode = (typeof VIEW_MODE)[keyof typeof VIEW_MODE];

// File manager filter types
export const FILTER_TYPE = {
  ALL: 'all',
  IMAGES: 'images',
  VIDEOS: 'videos',
  DOCUMENTS: 'documents',
  FOLDERS: 'folders',
} as const;

export type FilterType = (typeof FILTER_TYPE)[keyof typeof FILTER_TYPE];

// File types
export const FILE_TYPE = {
  FILE: 'file',
  FOLDER: 'folder',
} as const;

export type FileType = (typeof FILE_TYPE)[keyof typeof FILE_TYPE];

// Move/Copy modes
export const MOVE_COPY_MODE = {
  MOVE: 'move',
  COPY: 'copy',
} as const;

export type MoveCopyMode = (typeof MOVE_COPY_MODE)[keyof typeof MOVE_COPY_MODE];

// Initial pagination state for file manager (same values as PAGINATION defaults)
export const INITIAL_PAGINATION = {
  page: PAGINATION.DEFAULT_PAGE,
  pageSize: PAGINATION.DEFAULT_PER_PAGE,
  total: 0,
  totalPages: PAGINATION.DEFAULT_TOTAL_PAGES,
} as const;
