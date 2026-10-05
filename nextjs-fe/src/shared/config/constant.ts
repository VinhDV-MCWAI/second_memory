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

export type HttpMethod = typeof HTTP_METHODS[keyof typeof HTTP_METHODS];

// ============================================================================
// DATE & TIME
// ============================================================================

// Date & Time Formats
export const DATE_FORMATS = {
  // Display formats (for date-fns)
  SHORT: 'dd/MM/yy',
  DATE: 'dd/MM/yyyy',                    // Also: MEDIUM
  LONG: 'dd/MM/yyyy HH:mm',
  FULL: 'dd/MM/yyyy HH:mm:ss',           // Also: DATETIME
  TIME: 'HH:mm:ss',
  
  // Input formats
  DATE_INPUT: 'yyyy-MM-dd',              // HTML input[type="date"] format
  DATETIME_INPUT: 'yyyy-MM-dd HH:mm:ss', // Backend datetime format
  
  // Backend compatibility (back end formats for reference)
  BACKEND_DATE: 'd/m/Y',
  BACKEND_DATETIME: 'd/m/Y H:i:s',
} as const;

// Time Constants (milliseconds)
export const TIME_CONSTANTS = {
  ONE_HOUR: 3600000,
  TWO_DAYS: 172800000,
  ONE_DAY: 86400000,
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
  MAX_ACCESS_TTL: 60 * 5,               // 5 minutes
  MAX_REFRESH_TTL: 60 * 60 * 24 * 3,    // 3 days
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

export type SortOrder = typeof SORT_ORDER[keyof typeof SORT_ORDER];

// Common sort fields
export const SORT_FIELDS = {
  ID: 'id',
  NAME: 'name',
  CREATED_AT: 'created_at',
  UPDATED_AT: 'updated_at',
  ORDER: 'order',
  STATUS: 'status',
} as const;

export type SortField = typeof SORT_FIELDS[keyof typeof SORT_FIELDS];

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

// Media manager config (using same pagination defaults)
export const MEDIA_MANAGER_CONFIG = {
  MIN_PAGE: 1,
} as const;

// ============================================================================
// FORMS
// ============================================================================

// Default values for forms
export const FORM_DEFAULTS = {
  RANK_ORDER: 0,
} as const;

// ============================================================================
// FILE & MEDIA MANAGEMENT
// ============================================================================

// File upload constraints
export const FILE_UPLOAD = {
  MAX_AVATAR_SIZE_MB: 5,
  MAX_IMAGE_SIZE_MB: 10,
  MAX_FILE_SIZE_MB: 20,
  ACCEPTED_IMAGE_TYPES: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'] as const,
  ACCEPTED_AVATAR_TYPES: ['image/jpeg', 'image/png'] as const,
} as const;

// Upload progress & configuration
export const UPLOAD_CONFIG = {
  DEFAULT_ACCEPT: 'image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx',
  DEFAULT_MAX_SIZE_MB: 102400,               // 100GB
  DEFAULT_IMAGE_MAX_SIZE_MB: 5,          // 5MB (same as FILE_UPLOAD.MAX_IMAGE_SIZE_MB)
  DEFAULT_AVATAR_MAX_SIZE_MB: 5,         // 5MB (same as FILE_UPLOAD.MAX_AVATAR_SIZE_MB)
  PROGRESS_INCREMENT: 10,
  PROGRESS_INTERVAL_MS: 200,
  MIN_PROGRESS: 0,
  MAX_PROGRESS: 90,
  COMPLETE: 100,
  COMPLETE_DELAY_MS: 500,
  TEMP_UPLOAD_PATH: 'temp-uploads',
  HEAVY_FILE_THRESHOLD_BYTES: 100 * 1024 * 1024, // 100MB
} as const;

export const MULTIPART_UPLOAD_CONFIG = {
  MAX_RETRIES: 5,                    // Sync with backend
  CONCURRENT_UPLOADS: 6,             // Base value, will be overridden dynamically
  RETRY_DELAY_BASE: 1000,
  RETRY_JITTER: 500,                 // Increased for better distribution
  HTTP_STATUS_OK_MIN: 200,
  HTTP_STATUS_OK_MAX: 300,
  TIMEOUT_MS: 90000,                 // 90 seconds timeout per part (increased for large parts)
  
  // HTTP version-based concurrency limits
  HTTP_VERSION_LIMITS: {
    'HTTP/1.1': 6,
    'HTTP/2': 16,
    'HTTP/3': 32,
  } as const,
} as const;

// Part size configuration for dynamic calculation
export const PART_SIZE_CONFIG = {
  SMALL_FILE_THRESHOLD: 100 * 1024 * 1024,      // 100MB
  MEDIUM_FILE_THRESHOLD: 500 * 1024 * 1024,     // 500MB
  LARGE_FILE_THRESHOLD: 10 * 1024 * 1024 * 1024, // 10GB
  HUGE_FILE_THRESHOLD: 100 * 1024 * 1024 * 1024, // 100GB
  
  MIN_PART_SIZE_16MB: 16 * 1024 * 1024,
  MIN_PART_SIZE_32MB: 32 * 1024 * 1024,
  MIN_PART_SIZE_64MB: 64 * 1024 * 1024,
  MIN_PART_SIZE_128MB: 128 * 1024 * 1024,
  
  MAX_PARTS: 10000,
} as const;

// Media file categories & extensions
export const MEDIA = {
  TYPE_FILE: true,
  TYPE_FOLDER: false,
  
  MAX_FILE_SIZE: 107374182400, // 100GB
  
  // Categories
  CATEGORY_IMAGE: 'image',
  CATEGORY_VIDEO: 'video',
  CATEGORY_DOCUMENT: 'document',
  CATEGORY_ARCHIVE: 'archive',
  CATEGORY_OTHER: 'other',
  
  // Allowed Extensions
  ALLOWED_IMAGE_EXTENSIONS: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
  ALLOWED_VIDEO_EXTENSIONS: ['mp4', 'avi', 'mov', 'wmv', 'webm'],
  ALLOWED_AUDIO_EXTENSIONS: ['mp3', 'm4a', 'wav', 'ogg', 'flac', 'aac'],
  ALLOWED_DOCUMENT_EXTENSIONS: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
  ALLOWED_ARCHIVE_EXTENSIONS: ['zip', 'rar', '7z', 'tar', 'gz'],
} as const;

// Image shapes
export const IMAGE_SHAPES = {
  SQUARE: 'square',
  RECTANGLE: 'rectangle',
  CIRCLE: 'circle',
} as const;

export type ImageShape = typeof IMAGE_SHAPES[keyof typeof IMAGE_SHAPES];

// Export/Import formats
export const EXPORT_FORMATS = {
  CSV: 'csv',
  EXCEL: 'excel',
  JSON: 'json',
} as const;

export type ExportFormat = typeof EXPORT_FORMATS[keyof typeof EXPORT_FORMATS];
export type ImportFormat = typeof EXPORT_FORMATS.CSV | typeof EXPORT_FORMATS.EXCEL;

// Drag and drop events
export const DRAG_EVENTS = {
  ENTER: 'dragenter',
  OVER: 'dragover',
  LEAVE: 'dragleave',
  DROP: 'drop',
} as const;

// File type filters (for media)
export const MEDIA_FILE_TYPES = ['all', 'images', 'videos', 'documents'] as const;

// MIME type prefixes
export const MIME_TYPE_PREFIX = {
  IMAGE: 'image/',
  VIDEO: 'video/',
  AUDIO: 'audio/',
  APPLICATION: 'application/',
} as const;

// File size units
export const FILE_SIZE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB'] as const;

export const FILE_SIZE_MULTIPLIER = 1024;


// MIME type labels
export const MIME_TYPE_LABELS: Record<string, string> = {
  'image/jpeg': 'JPEG',
  'image/png': 'PNG',
  'image/gif': 'GIF',
  'image/webp': 'WebP',
  'video/mp4': 'MP4',
  'video/webm': 'WebM',
  'audio/mpeg': 'MP3',
  'application/pdf': 'PDF',
  'application/zip': 'ZIP',
  'application/vnd.google-apps.folder': 'Folder',
  'folder': 'Folder',
} as const;

// ============================================================================
// FILE MANAGER
// ============================================================================

// File manager sort fields
export const FILE_MANAGER_SORT_FIELDS = {
  NAME: 'name',
  DATE: 'date',
  SIZE: 'size',
  TYPE: 'type',
} as const;

export type FileManagerSortField = typeof FILE_MANAGER_SORT_FIELDS[keyof typeof FILE_MANAGER_SORT_FIELDS];

// File manager view modes
export const VIEW_MODE = {
  GRID: 'grid',
  LIST: 'list',
} as const;

export type ViewMode = typeof VIEW_MODE[keyof typeof VIEW_MODE];

// File manager filter types
export const FILTER_TYPE = {
  ALL: 'all',
  IMAGES: 'images',
  VIDEOS: 'videos',
  DOCUMENTS: 'documents',
  FOLDERS: 'folders',
} as const;

export type FilterType = typeof FILTER_TYPE[keyof typeof FILTER_TYPE];

// File types
export const FILE_TYPE = {
  FILE: 'file',
  FOLDER: 'folder',
} as const;

export type FileType = typeof FILE_TYPE[keyof typeof FILE_TYPE];

// File operation types
export const OPERATION_TYPE = {
  RENAME: 'rename',
  MOVE: 'move',
  COPY: 'copy',
  DELETE: 'delete',
  UPLOAD: 'upload',
  CREATE_FOLDER: 'create_folder',
} as const;

export type FileOperationType = typeof OPERATION_TYPE[keyof typeof OPERATION_TYPE];

// Move/Copy modes
export const MOVE_COPY_MODE = {
  MOVE: 'move',
  COPY: 'copy',
} as const;

export type MoveCopyMode = typeof MOVE_COPY_MODE[keyof typeof MOVE_COPY_MODE];

// Translation keys for file manager
export const TRANSLATION_KEY = {
  MOVE_FAILED: 'moveFailed',
  COPY_FAILED: 'copyFailed',
  MOVE_SUCCESS: 'moveSuccess',
  COPY_SUCCESS: 'copySuccess',
} as const;

// Initial pagination state for file manager (same values as PAGINATION defaults)
export const INITIAL_PAGINATION = {
  page: PAGINATION.DEFAULT_PAGE,
  pageSize: PAGINATION.DEFAULT_PER_PAGE,
  total: 0,
  totalPages: PAGINATION.DEFAULT_TOTAL_PAGES,
} as const;

// Default sidebar folders for file manager
export const DEFAULT_SIDEBAR_FOLDERS = [
  { id: '1', nameKey: 'sidebar.images', path: '/images/2025' },
  { id: '2', nameKey: 'sidebar.videos', path: '/videos' },
  { id: '3', nameKey: 'sidebar.documents', path: '/documents' },
  { id: '4', nameKey: 'sidebar.recent', path: '/recent' },
  { id: '5', nameKey: 'sidebar.shared', path: '/shared' },
] as const;

// ============================================================================
// UI & UX
// ============================================================================

// UI Constants
export const UI_CONSTANTS = {
  DEBOUNCE_MS: 300,
  SCROLL_TOP_THRESHOLD: 300,
  DEFAULT_SKELETON_ROWS: 5,
  TOOLTIP_DELAY_DURATION: 0,
  TOOLTIP_SIDE_OFFSET: 0,
  POPOVER_SIDE_OFFSET: 4,
  POPOVER_WIDTH: 72, // w-72 in Tailwind = 18rem = 288px
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

export type KeyboardKey = typeof KEYBOARD_KEYS[keyof typeof KEYBOARD_KEYS];

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

export type ThemeMode = typeof THEME[keyof typeof THEME];

// ============================================================================
// ROUTING
// ============================================================================

// Admin Routes
export const ADMIN_ROUTES = {
  DASHBOARD: '/admin',
  APIS: '/admin/apis',
  CATEGORIES: '/admin/categories',
  DEPARTMENTS: '/admin/departments',
  FEATURES: '/admin/features',
  ENTRIES: '/admin/entries',
  SOCIALS: '/admin/socials',
  TOKENS: '/admin/tokens',
  BANNERS: '/admin/banners',
  SLIDERS: '/admin/sliders',
  POLICY_DEPARTMENTS: '/admin/policy-departments',
  SETTING_LINKS: '/admin/setting-links',
  ENTRY_DESCRIPTIONS: '/admin/entry-descriptions',
  FILE_MANAGER: '/admin/file-manager',
  ADMINS: '/admin/admins',
  USERS: '/admin/users',
  ROLES: '/admin/roles',
  LOGIN: '/login',
  SETTINGS_GENERAL: '/admin/settings/general',
  SETTINGS_SECURITY: '/admin/settings/security',
  SETTINGS_NOTIFICATIONS: '/admin/settings/notifications',
} as const;
