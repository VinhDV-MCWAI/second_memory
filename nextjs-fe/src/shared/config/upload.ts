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
  DEFAULT_AVATAR_MAX_SIZE_MB: 5, // 5MB (same as FILE_UPLOAD.MAX_AVATAR_SIZE_MB)
  PROGRESS_INCREMENT: 10,
  PROGRESS_INTERVAL_MS: 200,
  MAX_PROGRESS: 90,
  COMPLETE_DELAY_MS: 500,
  HEAVY_FILE_THRESHOLD_BYTES: 100 * 1024 * 1024, // 100MB
} as const;

// MIME type prefixes
export const MIME_TYPE_PREFIX = {
  IMAGE: 'image/',
  VIDEO: 'video/',
  AUDIO: 'audio/',
  APPLICATION: 'application/',
} as const;
