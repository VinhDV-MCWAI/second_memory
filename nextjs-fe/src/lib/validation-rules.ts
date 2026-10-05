/**
 * Validation rules constants matching Laravel backend validation rules
 * These constants should match the validation rules in Laravel API
 */

export const ValidationRules = {
  // Email
  EMAIL_MAX: 255,

  // Username
  USERNAME_MIN: 3,
  USERNAME_MAX: 50,

  // Password
  PASSWORD_MIN: 8,
  PASSWORD_MAX: 255,

  // Name fields
  NAME_MAX: 255,

  // Address
  ADDRESS_MAX: 500,

  // Phone
  PHONE_MAX: 20,

  // Avatar
  AVATAR_MAX: 255,
} as const;
