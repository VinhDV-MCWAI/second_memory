/**
 * File upload, multipart upload and MIME constants.
 */

// File upload constraints
export const FILE_UPLOAD = {
  MAX_AVATAR_SIZE_MB: 5,
  MAX_IMAGE_SIZE_MB: 10,
} as const;

// Upload progress & configuration
export const UPLOAD_CONFIG = {
  DEFAULT_IMAGE_MAX_SIZE_MB: 5, // 5MB (same as FILE_UPLOAD.MAX_IMAGE_SIZE_MB)
  DEFAULT_AVATAR_MAX_SIZE_MB: 5, // 5MB (same as FILE_UPLOAD.MAX_AVATAR_SIZE_MB)
  PROGRESS_INCREMENT: 10,
  PROGRESS_INTERVAL_MS: 200,
  MAX_PROGRESS: 90,
  COMPLETE_DELAY_MS: 500,
  HEAVY_FILE_THRESHOLD_BYTES: 100 * 1024 * 1024, // 100MB
} as const;

export const MULTIPART_UPLOAD_CONFIG = {
  MAX_RETRIES: 5, // Sync with backend
  CONCURRENT_UPLOADS: 6, // Base value, will be overridden dynamically
  RETRY_DELAY_BASE: 1000,
  RETRY_JITTER: 500, // Increased for better distribution
  HTTP_STATUS_OK_MIN: 200,
  HTTP_STATUS_OK_MAX: 300,
  TIMEOUT_MS: 90000, // 90 seconds timeout per part (increased for large parts)

  // HTTP version-based concurrency limits
  HTTP_VERSION_LIMITS: {
    'HTTP/1.1': 6,
    'HTTP/2': 16,
    'HTTP/3': 32,
  } as const,
} as const;

// Image shapes
export const IMAGE_SHAPES = {
  SQUARE: 'square',
  RECTANGLE: 'rectangle',
  CIRCLE: 'circle',
} as const;

export type ImageShape = (typeof IMAGE_SHAPES)[keyof typeof IMAGE_SHAPES];

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
  folder: 'Folder',
} as const;
