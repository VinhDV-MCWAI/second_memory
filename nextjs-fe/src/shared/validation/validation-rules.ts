/**
 * Validation utilities matching back-end backend
 * Use these helpers across forms to ensure consistency with API
 */

import { VALIDATION } from '@/shared/config/constant';

export const ValidationRules = {
  // String lengths (matching back-end CommonVal)
  MIN_VARCHAR: VALIDATION.MIN_VARCHAR,
  MAX_VARCHAR: VALIDATION.MAX_VARCHAR,
  MAX_EMAIL: VALIDATION.MAX_EMAIL,
  MAX_PHONE_NUMBER: VALIDATION.MAX_PHONE_NUMBER,
  MAX_TEXT: VALIDATION.MAX_TEXT,

  // Integer ranges
  MIN_INTEGER: VALIDATION.MIN_INTEGER,
  MAX_INTEGER: VALIDATION.MAX_INTEGER,
  MAX_BIG_INTEGER: VALIDATION.MAX_BIG_INTEGER,

  // Date ranges
  MIN_DATE: VALIDATION.MIN_DATE,
  MAX_DATE: VALIDATION.MAX_DATE,

  // Specific field lengths (from back-end FormRequests)
  EMAIL_MAX: 30,
  USERNAME_MIN: 1,
  USERNAME_MAX: 50,
  PASSWORD_MIN: 6,
  PASSWORD_MAX: 100,
  NAME_MAX: 50,
  FIRST_NAME_MAX: 20,
  LAST_NAME_MAX: 20,
  ADDRESS_MAX: 100,
  PHONE_MAX: 20,
  AVATAR_MAX: 30,
  ROLE_NAME_MAX: 30,
  ROLE_PERMISSION_MAX: 50,
  CODE_MAX: 50,
  ICON_MAX: 50,
} as const;

/**
 * Format error messages consistently
 */
export const ErrorMessages = {
  required: (field: string) => `${field} is required`,
  minLength: (field: string, min: number) => `${field} must be at least ${min} characters`,
  maxLength: (field: string, max: number) => `${field} must be at most ${max} characters`,
  email: 'Invalid email format',
  url: 'Invalid URL format',
  integer: 'Must be a valid integer',
  minValue: (min: number) => `Must be at least ${min}`,
  maxValue: (max: number) => `Must be at most ${max}`,
  invalidEnum: (field: string) => `Invalid ${field} value`,
} as const;
