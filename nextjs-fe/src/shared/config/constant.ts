/**
 * ============================================================================
 * APPLICATION CONSTANTS
 * ============================================================================
 * Centralized constants for the entire application
 * Organized by functional areas for easy navigation
 */

// ============================================================================
// GENERAL
// ============================================================================

export const SUPPORT_EMAIL = 'support@example.com';

// ============================================================================
// HTTP
// ============================================================================

// HTTP Status Codes
export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  ACCEPTED: 202,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  METHOD_NOT_ALLOWED: 405,
  UNPROCESSABLE_CONTENT: 422,
  INTERNAL_SERVER_ERROR: 500,
} as const;

// HTTP Methods
export const HTTP_METHODS = {
  GET: 'GET',
  POST: 'POST',
  PUT: 'PUT',
  PATCH: 'PATCH',
  DELETE: 'DELETE',
} as const;

// ============================================================================
// DATE & TIME
// ============================================================================

// Date & Time Formats
export const DATE_FORMATS = {
  // Display formats (for date-fns)
  SHORT: 'dd/MM/yy',
  DATE: 'dd/MM/yyyy', // Also: MEDIUM
  LONG: 'dd/MM/yyyy HH:mm',
  FULL: 'dd/MM/yyyy HH:mm:ss', // Also: DATETIME
  TIME: 'HH:mm:ss',

  // Input formats
  DATE_INPUT: 'yyyy-MM-dd', // HTML input[type="date"] format
  DATETIME_INPUT: 'yyyy-MM-dd HH:mm:ss', // Backend datetime format

  // Backend compatibility (back end formats for reference)
  BACKEND_DATE: 'd/m/Y',
  BACKEND_DATETIME: 'd/m/Y H:i:s',
} as const;

// Time Constants (milliseconds)
export const TIME_CONSTANTS = {
  STALE_TIME: 5000,
} as const;

// ============================================================================
// VALIDATION
// ============================================================================

// Validation Limits
export const VALIDATION = {
  MIN_INTEGER: 0,
  MAX_INTEGER: 2147483647,
  MAX_BIG_INTEGER: 9223372036854775807,
  MIN_DATE: new Date('1900-01-01'),
  MAX_DATE: new Date('2100-12-31'),
  MIN_VARCHAR: 0,
  MAX_VARCHAR: 255,
  MAX_EMAIL: 254,
  MAX_PHONE_NUMBER: 20,
  MAX_TEXT: 65535,
} as const;

// ============================================================================
// AUTHENTICATION & SECURITY
// ============================================================================

// Authentication & Security
export const AUTH = {
  MAX_ACCESS_TTL: 60 * 5, // 5 minutes
  MAX_REFRESH_TTL: 60 * 60 * 24 * 3, // 3 days
  LIMIT_ACCESS_FAIL: 5,
  ADMIN_TYPE: 'admin',
} as const;

// ============================================================================
// SORTING & FILTERING
// ============================================================================

// Sort order
export const SORT_ORDER = {
  ASC: 'asc',
  DESC: 'desc',
} as const;

export type SortOrder = (typeof SORT_ORDER)[keyof typeof SORT_ORDER];

// Common sort fields
export const SORT_FIELDS = {
  ID: 'id',
  NAME: 'name',
  CREATED_AT: 'created_at',
  UPDATED_AT: 'updated_at',
  RANK_ORDER: 'rank_order',
  STATUS: 'status',
} as const;

export type SortField = (typeof SORT_FIELDS)[keyof typeof SORT_FIELDS];

// ============================================================================
// PAGINATION
// ============================================================================

// Pagination
export const PAGINATION = {
  DEFAULT_PAGE: 1,
  DEFAULT_PER_PAGE: 20,
  DEFAULT_TOTAL_PAGES: 1,
  MAX_PER_PAGE: 1000, // For fetching all items (e.g., dropdowns)
  PER_PAGE_OPTIONS: [10, 20, 50, 100] as const,
  DEFAULT_TOTAL: 0,
  DEFAULT_FROM: 0,
  DEFAULT_TO: 0,
} as const;

// Default values for forms
export const FORM_DEFAULTS = {
  RANK_ORDER: 0,
} as const;

// UI Constants
export const UI_CONSTANTS = {
  DEBOUNCE_MS: 300,
  SCROLL_TOP_THRESHOLD: 300,
  DEFAULT_SKELETON_ROWS: 5,
  POPOVER_SIDE_OFFSET: 4,
  DROPDOWN_MENU_SIDE_OFFSET: 4,
  LOADING_SKELETON_COUNT: 10, // For file manager
  ACTION_DELAY_MS: 300,
} as const;

// Keyboard keys
export const KEYBOARD_KEYS = {
  ESCAPE: 'Escape',
  ENTER: 'Enter',
  F2: 'F2',
  DELETE: 'Delete',
  SPACE: ' ',
  ARROW_UP: 'ArrowUp',
  ARROW_DOWN: 'ArrowDown',
  ARROW_LEFT: 'ArrowLeft',
  ARROW_RIGHT: 'ArrowRight',
} as const;

// Keyboard events
export const KEYBOARD_EVENT = {
  KEYDOWN: 'keydown',
  KEYUP: 'keyup',
  KEYPRESS: 'keypress',
} as const;

// ============================================================================
// THEME
// ============================================================================

// Theme Constants
export const THEME = {
  LIGHT: 'light',
  DARK: 'dark',
  SYSTEM: 'system',
} as const;

// Admin Routes
export const ADMIN_ROUTES = {
  DASHBOARD: '/admin',
  APIS: '/admin/apis',
  CATEGORIES: '/admin/categories',
  FEATURES: '/admin/features',
  ENTRIES: '/admin/entries',
  TOKENS: '/admin/tokens',
  ENTRY_DESCRIPTIONS: '/admin/entry-descriptions',
  FILE_MANAGER: '/admin/file-manager',
  ADMINS: '/admin/admins',
  ROLES: '/admin/roles',
  LOGIN: '/login',
} as const;
