/**
 * Validation utilities matching back-end backend
 * Use these helpers across forms to ensure consistency with API
 */

import { VALIDATION } from '@/shared/config';

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
  /** End users of the public site (EndUserConst::PASSWORD_MIN) */
  END_USER_PASSWORD_MIN: 8,
  NAME_MAX: 50,
  FIRST_NAME_MAX: 20,
  LAST_NAME_MAX: 20,
  ADDRESS_MAX: 100,
  PHONE_MAX: 20,
  AVATAR_MAX: 30,
  ROLE_NAME_MAX: 50, // laravel-api PermissionConst::ROLE_NAME_MAX
  ROLE_PERMISSION_MAX: 50,
  // laravel-api DepartmentConst
  DEPARTMENT_CODE_MAX: 30,
  DEPARTMENT_NAME_MAX: 100,
  DEPARTMENT_DESCRIPTION_MAX: 1000,
  CODE_MAX: 50,
  ICON_MAX: 50,

  // Public home page content (laravel-api ContentConst)
  CATEGORY_NAME_MAX: 100,
  CATEGORY_DESCRIPTION_MAX: 500,
  ARTICLE_TITLE_MAX: 200,
  ARTICLE_SUMMARY_MAX: 500,
  ARTICLE_BODY_MAX: 100000,
  SORT_ORDER_MAX: 9999,
} as const;
