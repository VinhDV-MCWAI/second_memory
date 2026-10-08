import { z } from 'zod';
import { Gender, AdminStatus, AdminRole, SkillLevel } from '@/shared/enums';
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
    role: z.nativeEnum(AdminRole),
    is_active: z.boolean(),
    avatar: getAvatarValidation(t),
  });

export type AdminFormData = z.infer<ReturnType<typeof getAdminSchema>>;

/** Today as `YYYY-MM-DD` in the browser's time zone (level changes cannot be in the future). */
const today = () => {
  const now = new Date();
  return new Date(now.getTime() - now.getTimezoneOffset() * 60_000).toISOString().slice(0, 10);
};

// Tag schema matching StoreTagRequest
export const getTagSchema = (t: Translator) =>
  z.object({
    name: z
      .string()
      .trim()
      .min(1, t('tagName.required'))
      .max(
        ValidationRules.TAG_NAME_MAX,
        t('tagName.maxLength', { max: ValidationRules.TAG_NAME_MAX }),
      ),
  });

export type TagFormData = z.infer<ReturnType<typeof getTagSchema>>;

// Level entry matching StoreSkillLevelRequest (without skill_id)
export const getSkillLevelSchema = (t: Translator) =>
  z.object({
    level: z.nativeEnum(SkillLevel, { message: t('level.required') }),
    changed_on: z.string().refine((value) => value === '' || value <= today(), {
      message: t('changedOn.future'),
    }),
    reason: z
      .string()
      .max(
        ValidationRules.LEVEL_REASON_MAX,
        t('reason.maxLength', { max: ValidationRules.LEVEL_REASON_MAX }),
      ),
  });

export type SkillLevelFormData = z.infer<ReturnType<typeof getSkillLevelSchema>>;

// Skill schema matching StoreSkillRequest; the level fields are sent on create only (UpdateSkillRequest has none)
export const getSkillSchema = (t: Translator) =>
  getSkillLevelSchema(t).extend({
    name: z
      .string()
      .trim()
      .min(1, t('skillName.required'))
      .max(
        ValidationRules.SKILL_NAME_MAX,
        t('skillName.maxLength', { max: ValidationRules.SKILL_NAME_MAX }),
      ),
    category: z
      .string()
      .trim()
      .min(1, t('category.required'))
      .max(
        ValidationRules.SKILL_CATEGORY_MAX,
        t('category.maxLength', { max: ValidationRules.SKILL_CATEGORY_MAX }),
      ),
    description: z
      .string()
      .max(
        ValidationRules.SKILL_DESCRIPTION_MAX,
        t('description.maxLength', { max: ValidationRules.SKILL_DESCRIPTION_MAX }),
      ),
    is_public: z.boolean(),
    tag_ids: z.array(z.number()),
  });

export type SkillFormData = z.infer<ReturnType<typeof getSkillSchema>>;
