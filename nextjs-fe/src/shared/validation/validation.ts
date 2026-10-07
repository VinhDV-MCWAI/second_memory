import { z } from 'zod';
import { Gender, AdminStatus, FeatureStatus } from '@/shared/enums';
import { ValidationRules } from './validation-rules';

type Translator = (key: string, params?: Record<string, string | number>) => string;

// Base validation schemas matching back end backend
export const getEmailValidation = (t: Translator) =>
  z
    .string()
    .min(1, t('email.required'))
    .max(ValidationRules.EMAIL_MAX, t('email.maxLength', { max: ValidationRules.EMAIL_MAX }))
    .email(t('email.invalid'));

export const getUsernameValidation = (t: Translator) =>
  z
    .string()
    .min(
      ValidationRules.USERNAME_MIN,
      t('username.minLength', { min: ValidationRules.USERNAME_MIN }),
    )
    .max(
      ValidationRules.USERNAME_MAX,
      t('username.maxLength', { max: ValidationRules.USERNAME_MAX }),
    );

export const getPasswordValidation = (t: Translator) =>
  z
    .string()
    .min(
      ValidationRules.PASSWORD_MIN,
      t('password.minLength', { min: ValidationRules.PASSWORD_MIN }),
    )
    .max(
      ValidationRules.PASSWORD_MAX,
      t('password.maxLength', { max: ValidationRules.PASSWORD_MAX }),
    );

export const getFirstNameValidation = (t: Translator) =>
  z
    .string()
    .min(1, t('firstName.required'))
    .max(ValidationRules.NAME_MAX, t('firstName.maxLength', { max: ValidationRules.NAME_MAX }));

export const getLastNameValidation = (t: Translator) =>
  z
    .string()
    .min(1, t('lastName.required'))
    .max(ValidationRules.NAME_MAX, t('lastName.maxLength', { max: ValidationRules.NAME_MAX }));

export const getAddressValidation = (t: Translator) =>
  z
    .string()
    .max(ValidationRules.ADDRESS_MAX, t('address.maxLength', { max: ValidationRules.ADDRESS_MAX }))
    .optional();

export const getPhoneValidation = (t: Translator) =>
  z
    .string()
    .max(ValidationRules.PHONE_MAX, t('phone.maxLength', { max: ValidationRules.PHONE_MAX }))
    .optional();

export const getAvatarValidation = (t: Translator) =>
  z
    .string()
    .max(ValidationRules.AVATAR_MAX, t('avatar.maxLength', { max: ValidationRules.AVATAR_MAX }))
    .optional();

// Forced casting to ZodType to ensure TS infers the Enum type instead of unknown
export const genderValidation = z.nativeEnum(Gender);
export const adminStatusValidation = z.nativeEnum(AdminStatus);
export const featureStatusValidation = z.nativeEnum(FeatureStatus);

// Admin schema matching StoreAdminMstRequest
export const getAdminSchema = (t: Translator) =>
  z.object({
    email: getEmailValidation(t),
    user_name: getUsernameValidation(t),
    password: z.union([getPasswordValidation(t), z.literal('')]).optional(),
    first_name: getFirstNameValidation(t),
    last_name: getLastNameValidation(t),
    address: getAddressValidation(t),
    phone_number: getPhoneValidation(t),
    birth: z.string().optional(),
    gender: genderValidation,
    status: adminStatusValidation,
    is_active: z.boolean(),
    avatar: getAvatarValidation(t),
  });

export type AdminFormData = z.infer<ReturnType<typeof getAdminSchema>>;

// Role schema
export const getRoleSchema = (t: Translator) =>
  z.object({
    name: z
      .string()
      .min(1, t('name.required'))
      .max(30, t('name.maxLength', { max: 30 })),
    permission: z
      .string()
      .min(1, t('permission.required'))
      .max(50, t('permission.maxLength', { max: 50 })),
    is_active: z.boolean(),
  });

export type RoleFormData = z.infer<ReturnType<typeof getRoleSchema>>;

// Feature schema
export const getFeatureSchema = (t: Translator) =>
  z.object({
    name: z.string().min(1, t('name.required')),
    group_name: z
      .string()
      .min(1, t('groupName.required'))
      .max(50, t('groupName.maxLength', { max: 50 })),
    status: featureStatusValidation,
  });

export type FeatureFormData = z.infer<ReturnType<typeof getFeatureSchema>>;

// API schema
export const getApiSchema = (t: Translator) =>
  z.object({
    name: z.string().min(1, t('name.required')),
    path: z.string().min(1, t('path.required')),
    type: z.number().min(0, t('type.required')),
    method: z.enum(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']).optional(),
    description: z.string().optional(),
    feature_mst_id: z.number().min(1, t('feature.required')),
    is_active: z.boolean(),
  });

export type ApiFormData = z.infer<ReturnType<typeof getApiSchema>>;

// Token schema
export const getTokenSchema = (t: Translator) =>
  z.object({
    account_id: z.number().min(1, t('account.required')),
    device_name: z.string().min(1, t('deviceName.required')),
    ip_address: z.string().optional(),
    expired_at: z.string().optional(),
  });

export type TokenFormData = z.infer<ReturnType<typeof getTokenSchema>>;
