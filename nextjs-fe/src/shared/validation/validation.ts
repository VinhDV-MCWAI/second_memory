import { z } from 'zod';
import { Gender, AdminStatus } from '@/shared/enums';
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
    role_ids: z.array(z.number()),
    department_ids: z.array(z.number()),
    is_active: z.boolean(),
    avatar: getAvatarValidation(t),
  });

export type AdminFormData = z.infer<ReturnType<typeof getAdminSchema>>;

/** http(s) only, like the API's `url:http,https` rule: no javascript: or data: links. */
const isHttpUrl = (value: string) => {
  try {
    return ['http:', 'https:'].includes(new URL(value).protocol);
  } catch {
    return false;
  }
};

/** Evidence of a category: a link, a file of the file manager, or both (ADR-0022). */
const getAttachmentSchema = (t: Translator) =>
  z
    .object({
      id: z.number().int().optional(),
      title: z
        .string()
        .trim()
        .min(1, t('attachmentTitle.required'))
        .max(
          ValidationRules.ATTACHMENT_TITLE_MAX,
          t('attachmentTitle.maxLength', { max: ValidationRules.ATTACHMENT_TITLE_MAX }),
        ),
      url: z
        .string()
        .trim()
        .max(
          ValidationRules.ATTACHMENT_URL_MAX,
          t('url.maxLength', { max: ValidationRules.ATTACHMENT_URL_MAX }),
        )
        .refine((value) => value === '' || isHttpUrl(value), t('url.invalid')),
      media_id: z.number().int().nullable(),
      note: z
        .string()
        .max(
          ValidationRules.ATTACHMENT_NOTE_MAX,
          t('note.maxLength', { max: ValidationRules.ATTACHMENT_NOTE_MAX }),
        ),
    })
    .refine((item) => item.url !== '' || item.media_id !== null, {
      message: t('url.linkOrFile'),
      path: ['url'],
    });

export type AttachmentFormData = z.infer<ReturnType<typeof getAttachmentSchema>>;

// Group or category, matching StoreContentCategoryRequest (ADR-0022): the description is Markdown
export const getContentCategorySchema = (t: Translator) =>
  z.object({
    name: z
      .string()
      .trim()
      .min(1, t('categoryName.required'))
      .max(
        ValidationRules.CATEGORY_NAME_MAX,
        t('categoryName.maxLength', { max: ValidationRules.CATEGORY_NAME_MAX }),
      ),
    description: z
      .string()
      .max(
        ValidationRules.CATEGORY_DESCRIPTION_MAX,
        t('description.maxLength', { max: ValidationRules.CATEGORY_DESCRIPTION_MAX }),
      ),
    parent_id: z.number().int().nullable(),
    is_public: z.boolean(),
    attachments: z.array(getAttachmentSchema(t)).max(ValidationRules.ATTACHMENTS_MAX),
  });

export type ContentCategoryFormData = z.infer<ReturnType<typeof getContentCategorySchema>>;

// Article, matching StoreArticleRequest (ADR-0022): the body is Markdown, any number of categories
export const getArticleSchema = (t: Translator) =>
  z.object({
    parent_id: z.number().int().nullable(),
    category_ids: z.array(z.number().int()),
    title: z
      .string()
      .trim()
      .min(1, t('articleTitle.required'))
      .max(
        ValidationRules.ARTICLE_TITLE_MAX,
        t('articleTitle.maxLength', { max: ValidationRules.ARTICLE_TITLE_MAX }),
      ),
    summary: z
      .string()
      .max(
        ValidationRules.ARTICLE_SUMMARY_MAX,
        t('summary.maxLength', { max: ValidationRules.ARTICLE_SUMMARY_MAX }),
      ),
    body: z
      .string()
      .trim()
      .min(1, t('articleBody.required'))
      .max(
        ValidationRules.ARTICLE_BODY_MAX,
        t('articleBody.maxLength', { max: ValidationRules.ARTICLE_BODY_MAX }),
      ),
    is_public: z.boolean(),
  });

export type ArticleFormData = z.infer<ReturnType<typeof getArticleSchema>>;

// Department schema matching StoreDepartmentRequest (ADR-0017)
export const getDepartmentSchema = (t: Translator) =>
  z.object({
    code: z
      .string()
      .trim()
      .min(1, t('departmentCode.required'))
      .max(
        ValidationRules.DEPARTMENT_CODE_MAX,
        t('departmentCode.maxLength', { max: ValidationRules.DEPARTMENT_CODE_MAX }),
      ),
    name: z
      .string()
      .trim()
      .min(1, t('departmentName.required'))
      .max(
        ValidationRules.DEPARTMENT_NAME_MAX,
        t('departmentName.maxLength', { max: ValidationRules.DEPARTMENT_NAME_MAX }),
      ),
    description: z
      .string()
      .max(
        ValidationRules.DEPARTMENT_DESCRIPTION_MAX,
        t('description.maxLength', { max: ValidationRules.DEPARTMENT_DESCRIPTION_MAX }),
      ),
    is_active: z.boolean(),
  });

export type DepartmentFormData = z.infer<ReturnType<typeof getDepartmentSchema>>;

// End user (ADR-0020), matching UpdateEndUserRequest: lock / unlock, optional new password
export const getEndUserSchema = (t: Translator) =>
  z.object({
    is_active: z.boolean(),
    password: z.union([
      z
        .string()
        .min(
          ValidationRules.END_USER_PASSWORD_MIN,
          t('password.minLength', { min: ValidationRules.END_USER_PASSWORD_MIN }),
        )
        .max(
          ValidationRules.PASSWORD_MAX,
          t('password.maxLength', { max: ValidationRules.PASSWORD_MAX }),
        ),
      z.literal(''),
    ]),
  });

export type EndUserFormData = z.infer<ReturnType<typeof getEndUserSchema>>;

// Role schema matching StoreRoleRequest (ADR-0013)
export const getRoleSchema = (t: Translator) =>
  z.object({
    name: z
      .string()
      .trim()
      .min(1, t('roleName.required'))
      .max(
        ValidationRules.ROLE_NAME_MAX,
        t('roleName.maxLength', { max: ValidationRules.ROLE_NAME_MAX }),
      ),
    permissions: z.array(z.string()),
  });

export type RoleFormData = z.infer<ReturnType<typeof getRoleSchema>>;
