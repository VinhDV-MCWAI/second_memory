import { z } from 'zod';
import { Gender, StatusEnum, AdminStatus, UserStatus, CategoryStatus, DepartmentStatus, FeatureStatus, EntryStatus, SocialStatus } from '@/shared/enums';
import { ValidationRules } from './validation-rules';

type Translator = (key: string, params?: Record<string, string | number>) => string;

// Base validation schemas matching back end backend
export const getEmailValidation = (t: Translator) => z.string()
  .min(1, t('email.required'))
  .max(ValidationRules.EMAIL_MAX, t('email.maxLength', { max: ValidationRules.EMAIL_MAX }))
  .email(t('email.invalid'));

export const getUsernameValidation = (t: Translator) => z.string()
  .min(ValidationRules.USERNAME_MIN, t('username.minLength', { min: ValidationRules.USERNAME_MIN }))
  .max(ValidationRules.USERNAME_MAX, t('username.maxLength', { max: ValidationRules.USERNAME_MAX }));

export const getPasswordValidation = (t: Translator) => z.string()
  .min(ValidationRules.PASSWORD_MIN, t('password.minLength', { min: ValidationRules.PASSWORD_MIN }))
  .max(ValidationRules.PASSWORD_MAX, t('password.maxLength', { max: ValidationRules.PASSWORD_MAX }));

export const getFirstNameValidation = (t: Translator) => z.string()
  .min(1, t('firstName.required'))
  .max(ValidationRules.NAME_MAX, t('firstName.maxLength', { max: ValidationRules.NAME_MAX }));

export const getLastNameValidation = (t: Translator) => z.string()
  .min(1, t('lastName.required'))
  .max(ValidationRules.NAME_MAX, t('lastName.maxLength', { max: ValidationRules.NAME_MAX }));

export const getAddressValidation = (t: Translator) => z.string()
  .max(ValidationRules.ADDRESS_MAX, t('address.maxLength', { max: ValidationRules.ADDRESS_MAX }))
  .optional();

export const getPhoneValidation = (t: Translator) => z.string()
  .max(ValidationRules.PHONE_MAX, t('phone.maxLength', { max: ValidationRules.PHONE_MAX }))
  .optional();

export const getAvatarValidation = (t: Translator) => z.string()
  .max(ValidationRules.AVATAR_MAX, t('avatar.maxLength', { max: ValidationRules.AVATAR_MAX }))
  .optional();

// Forced casting to ZodType to ensure TS infers the Enum type instead of unknown
export const genderValidation = z.nativeEnum(Gender);
export const statusValidation = z.nativeEnum(StatusEnum);
export const adminStatusValidation = z.nativeEnum(AdminStatus);
export const categoryStatusValidation = z.preprocess(
  (val) => (typeof val === 'string' ? Number(val) : val),
  z.nativeEnum(CategoryStatus)
);
export const departmentStatusValidation = z.nativeEnum(DepartmentStatus);
export const featureStatusValidation = z.nativeEnum(FeatureStatus);
export const userStatusValidation = z.nativeEnum(UserStatus);
export const entryStatusValidation = z.preprocess(
  (val) => (typeof val === 'string' ? Number(val) : val),
  z.nativeEnum(EntryStatus)
);
export const socialStatusValidation = z.nativeEnum(SocialStatus);

// Admin schema matching StoreAdminMstRequest
export const getAdminSchema = (t: Translator) => z.object({
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

// User schema
export const getUserSchema = (t: Translator) => z.object({
  email: getEmailValidation(t),
  user_name: getUsernameValidation(t),
  password: z.union([getPasswordValidation(t), z.literal('')]).optional().nullable(),
  first_name: getFirstNameValidation(t),
  last_name: getLastNameValidation(t),
  address: getAddressValidation(t),
  phone_number: getPhoneValidation(t),
  birth: z.string().optional(),
  gender: genderValidation,
  status: userStatusValidation,
  is_active: z.boolean(),
  avatar: getAvatarValidation(t),
});

export type UserFormData = z.infer<ReturnType<typeof getUserSchema>>;

// Category schema
export const getCategorySchema = (t: Translator) => z.object({
  name: z.string().min(1, t('name.required')),
  slug: z.string().min(1, t('slug.required')),
  description: z.string().optional(),
  status: categoryStatusValidation,
  is_display: z.boolean(),
  rank_order: z.number().min(0, t('order.min', { min: 0 })),
  is_delete: z.boolean(),
});

export type CategoryFormData = z.infer<ReturnType<typeof getCategorySchema>>;

// Entry schema
export const getEntrySchema = (t: Translator) => z.object({
  name: z.string().min(1, t('name.required')),
  slug: z.string().optional(),
  rank_order: z.number().min(0, t('order.min', { min: 0 })),
  status: entryStatusValidation,
  is_display: z.boolean().optional(),
});

export type EntryFormData = z.infer<ReturnType<typeof getEntrySchema>>;

// Role schema
export const getRoleSchema = (t: Translator) => z.object({
  name: z.string().min(1, t('name.required')).max(30, t('name.maxLength', { max: 30 })),
  permission: z.string().min(1, t('permission.required')).max(50, t('permission.maxLength', { max: 50 })),
  is_active: z.boolean(),
});

export type RoleFormData = z.infer<ReturnType<typeof getRoleSchema>>;

// Department schema
export const getDepartmentSchema = (t: Translator) => z.object({
  code: z.string().min(1, t('code.required')).max(50, t('code.maxLength', { max: 50 })),
  name: z.string().min(1, t('name.required')),
  status: departmentStatusValidation,
});

export type DepartmentFormData = z.infer<ReturnType<typeof getDepartmentSchema>>;

// Banner schema
export const getBannerSchema = (t: Translator) => z.object({
  title: z.string().min(1, t('title.required')),
  slug: z.string().min(1, t('slug.required')),
  description: z.string().min(1, t('description.required')),
  image: z.string().min(1, t('image.required')),
  position: z.string().min(1, t('position.required')),
  status: statusValidation,
});

export type BannerFormData = z.infer<ReturnType<typeof getBannerSchema>>;

// Feature schema
export const getFeatureSchema = (t: Translator) => z.object({
  name: z.string().min(1, t('name.required')),
  group_name: z.string().min(1, t('groupName.required')).max(50, t('groupName.maxLength', { max: 50 })),
  status: featureStatusValidation,
});

export type FeatureFormData = z.infer<ReturnType<typeof getFeatureSchema>>;

// Slider schema
export const getSliderSchema = (t: Translator) => z.object({
  title: z.string().min(1, t('title.required')),
  slug: z.string().optional(),
  link: z.string().optional(),
  image: z.string().optional(),
  status: statusValidation,
});

export type SliderFormData = z.infer<ReturnType<typeof getSliderSchema>>;

// Social schema
export const getSocialSchema = (t: Translator) => z.object({
  name: z.string().min(1, t('name.required')),
  slug: z.string().optional(),
  link: z.string().url(t('url.invalid')),
  image: z.string().optional(),
  rank_order: z.coerce.number().min(0, t('order.min', { min: 0 })),
  status: socialStatusValidation,
  is_display: z.boolean().optional(),
});

export type SocialFormData = z.infer<ReturnType<typeof getSocialSchema>>;

// Entry Description schema
export const getEntryDescriptionSchema = (t: Translator) => z.object({
  title: z.string().min(1, t('title.required')),
  summary: z.string().optional(),
  article: z.string().optional(),
  rank_order: z.number().min(0, t('order.min', { min: 0 })),
  status: statusValidation,
  is_display: z.boolean().optional(),
});

export type EntryDescriptionFormData = z.infer<ReturnType<typeof getEntryDescriptionSchema>>;

// API schema
export const getApiSchema = (t: Translator) => z.object({
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
export const getTokenSchema = (t: Translator) => z.object({
  account_id: z.number().min(1, t('account.required')),
  device_name: z.string().min(1, t('deviceName.required')),
  ip_address: z.string().optional(),
  expired_at: z.string().optional(),
});

export type TokenFormData = z.infer<ReturnType<typeof getTokenSchema>>;

// Setting Link schema
export const getSettingLinkSchema = (t: Translator) => z.object({
  name: z.string().min(1, t('name.required')),
  url: z.string().url(t('url.invalid')),
  description: z.string().optional(),
  rank_order: z.coerce.number().min(0, t('order.min', { min: 0 })),
  status: statusValidation,
  is_active: z.boolean(),
});

export type SettingLinkFormData = z.infer<ReturnType<typeof getSettingLinkSchema>>;

// Policy Department schema
export const getPolicyDepartmentSchema = (t: Translator) => z.object({
  table_name: z.string().min(1, t('tableName.required')),
  row_id: z.number().min(1, t('rowId.required')),
});

export type PolicyDepartmentFormData = z.infer<ReturnType<typeof getPolicyDepartmentSchema>>;

// Post schema
export const getPostSchema = (t: Translator) => z.object({
  id: z.number().optional(),
  title: z.string().min(1, t('title.required')),
  slug: z.string().min(1, t('slug.required')),
  content: z.string().min(1, t('content.required')),
  excerpt: z.string().optional(),
  featured_image: z.string().url(t('url.invalid')).optional(),
  status: statusValidation,
  is_active: z.boolean(),
  category_id: z.number().int().positive(t('category.required')),
  author_id: z.number().int().positive().optional(),
  published_at: z.string().optional(),
});

export type PostFormData = z.infer<ReturnType<typeof getPostSchema>>;

// Generic CRUD item schema
export const getCrudItemSchema = (t: Translator) => z.object({
  id: z.number().optional(),
  name: z.string().min(1, t('name.required')),
  description: z.string().optional(),
  status: statusValidation,
  is_active: z.boolean(),
});

export type CrudItemFormData = z.infer<ReturnType<typeof getCrudItemSchema>>;

// Login schema
export const getLoginSchema = (t: Translator) => z.object({
  user_name: z.string().min(1, t('username.required', { field: 'Username' })),
  password: z.string().min(1, t('password.required')),
});

export type LoginFormData = z.infer<ReturnType<typeof getLoginSchema>>;

// Pagination params schema
export const paginationParamsSchema = z.object({
  page: z.number().int().positive().default(1),
  per_page: z.number().int().positive().max(100).default(20),
  sort_by: z.string().optional(),
  sort_order: z.enum(['asc', 'desc']).default('asc'),
});

export type PaginationParams = z.infer<typeof paginationParamsSchema>;
