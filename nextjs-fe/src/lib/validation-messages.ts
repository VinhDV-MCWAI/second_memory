/**
 * Helper function to create validation messages with i18n support
 * This allows Zod error messages to be translated using next-intl
 */

import { ValidationRules } from './validation-rules.js';

/**
 * Get validation message key with parameters
 * Usage in components with useTranslations:
 * 
 * const t = useTranslations();
 * const errorMessage = t(error.message, { min: ValidationRules.PASSWORD_MIN });
 */
export const ValidationMessages = {
  email: {
    required: 'validation.email.required',
    invalid: 'validation.email.invalid',
    maxLength: 'validation.email.maxLength',
  },
  username: {
    minLength: 'validation.username.minLength',
    maxLength: 'validation.username.maxLength',
  },
  password: {
    required: 'validation.password.required',
    minLength: 'validation.password.minLength',
    maxLength: 'validation.password.maxLength',
  },
  firstName: {
    required: 'validation.firstName.required',
    maxLength: 'validation.firstName.maxLength',
  },
  lastName: {
    required: 'validation.lastName.required',
    maxLength: 'validation.lastName.maxLength',
  },
  address: {
    maxLength: 'validation.address.maxLength',
  },
  phone: {
    maxLength: 'validation.phone.maxLength',
  },
  avatar: {
    maxLength: 'validation.avatar.maxLength',
  },
  name: {
    required: 'validation.name.required',
    maxLength: 'validation.name.maxLength',
  },
  title: {
    required: 'validation.title.required',
  },
  content: {
    required: 'validation.content.required',
  },
  slug: {
    required: 'validation.slug.required',
  },
  url: {
    required: 'validation.url.required',
    invalid: 'validation.url.invalid',
  },
  order: {
    min: 'validation.order.min',
  },
  code: {
    required: 'validation.code.required',
    maxLength: 'validation.code.maxLength',
  },
  permission: {
    required: 'validation.permission.required',
    maxLength: 'validation.permission.maxLength',
  },
  entry: {
    required: 'validation.entry.required',
  },
  feature: {
    required: 'validation.feature.required',
  },
  category: {
    required: 'validation.category.required',
  },
  account: {
    required: 'validation.account.required',
  },
  deviceName: {
    required: 'validation.deviceName.required',
  },
  type: {
    required: 'validation.type.required',
  },
  path: {
    required: 'validation.path.required',
  },
  tableName: {
    required: 'validation.tableName.required',
  },
  rowId: {
    required: 'validation.rowId.required',
  },
  required: 'validation.required',
} as const;

/**
 * Helper function to format validation messages with parameters
 * This is used when displaying errors in forms
 * 
 * @param messageKey - The validation message key (e.g., 'validation.email.maxLength')
 * @param params - Parameters to replace in the message (e.g., { max: 255 })
 * @param t - The translation function from useTranslations()
 * @returns Formatted message
 */
export function formatValidationMessage(
  messageKey: string,
  params: Record<string, string | number>,
  t: (key: string, params?: Record<string, string | number>) => string
): string {
  return t(messageKey, params);
}

/**
 * Get validation rule values for use in error messages
 */
export function getValidationParams() {
  return {
    emailMax: ValidationRules.EMAIL_MAX,
    usernameMin: ValidationRules.USERNAME_MIN,
    usernameMax: ValidationRules.USERNAME_MAX,
    passwordMin: ValidationRules.PASSWORD_MIN,
    passwordMax: ValidationRules.PASSWORD_MAX,
    nameMax: ValidationRules.NAME_MAX,
    addressMax: ValidationRules.ADDRESS_MAX,
    phoneMax: ValidationRules.PHONE_MAX,
    avatarMax: ValidationRules.AVATAR_MAX,
  };
}
