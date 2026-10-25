import { describe, expect, it } from 'vitest';
import { getAdminSchema, getContentCategorySchema } from './validation';
import { ValidationRules } from './validation-rules';
import { AdminStatus, Gender } from '@/shared/enums';

// Echo the key and params so assertions can check which message was chosen.
const t = (key: string, params?: Record<string, string | number>) =>
  params ? `${key}:${JSON.stringify(params)}` : key;

const messagesOf = (result: { success: boolean; error?: { issues: { message: string }[] } }) =>
  result.success ? [] : result.error!.issues.map((issue) => issue.message);

const person = {
  email: 'admin@example.com',
  user_name: 'admin',
  first_name: 'Ada',
  last_name: 'Lovelace',
  gender: Gender.MALE,
  role_ids: [],
  department_ids: [],
  is_active: true,
};

describe('admin schema', () => {
  it('accept a valid record and an empty password (keep current password)', () => {
    expect(
      getAdminSchema(t).safeParse({ ...person, status: AdminStatus.ACTIVE, password: '' }).success,
    ).toBe(true);
  });

  it('report the backend length limits', () => {
    const result = getAdminSchema(t).safeParse({
      ...person,
      status: AdminStatus.ACTIVE,
      email: `${'a'.repeat(ValidationRules.EMAIL_MAX)}@x.io`,
      password: '123',
      phone_number: '1'.repeat(ValidationRules.PHONE_MAX + 1),
    });
    expect(messagesOf(result)).toEqual([
      `email.maxLength:{"max":${ValidationRules.EMAIL_MAX}}`,
      `password.minLength:{"min":${ValidationRules.PASSWORD_MIN}}`,
      `phone.maxLength:{"max":${ValidationRules.PHONE_MAX}}`,
    ]);
  });

  it('accept a list of role ids only', () => {
    const valid = { ...person, status: AdminStatus.ACTIVE, password: '' };
    expect(getAdminSchema(t).safeParse({ ...valid, role_ids: [1, 2] }).success).toBe(true);
    expect(getAdminSchema(t).safeParse({ ...valid, role_ids: ['admin'] }).success).toBe(false);
  });

  it('accept a list of department ids only', () => {
    const valid = { ...person, status: AdminStatus.ACTIVE, password: '' };
    expect(getAdminSchema(t).safeParse({ ...valid, department_ids: [3] }).success).toBe(true);
    expect(getAdminSchema(t).safeParse({ ...valid, department_ids: ['hr'] }).success).toBe(false);
  });

  it('cap first and last names at 20 characters and keep birth dates in range, like the API', () => {
    const result = getAdminSchema(t).safeParse({
      ...person,
      status: AdminStatus.ACTIVE,
      first_name: 'a'.repeat(ValidationRules.FIRST_NAME_MAX + 1),
      last_name: 'a'.repeat(ValidationRules.LAST_NAME_MAX),
      birth: '1899-12-31',
    });
    expect(messagesOf(result)).toEqual([
      `firstName.maxLength:{"max":${ValidationRules.FIRST_NAME_MAX}}`,
      'birth.range:{"min":1900,"max":2100}',
    ]);
  });

  it('want a password for a new admin only', () => {
    const valid = { ...person, status: AdminStatus.ACTIVE, password: '' };
    expect(messagesOf(getAdminSchema(t, true).safeParse(valid))).toEqual(['password.required']);
    expect(getAdminSchema(t, true).safeParse({ ...valid, password: '123456' }).success).toBe(true);
    expect(getAdminSchema(t).safeParse(valid).success).toBe(true);
  });

  it('reject an unknown status', () => {
    expect(getAdminSchema(t).safeParse({ ...person, status: 99 }).success).toBe(false);
  });
});

describe('category schema', () => {
  const category = (url: string) => ({
    parent_id: null,
    name: 'Laravel',
    description: '',
    is_public: true,
    attachments: [{ title: 'Docs', url, media_id: null, note: '' }],
  });

  it('accept http and https links', () => {
    for (const url of ['https://laravel.com/docs', 'http://localhost:81/a?b=1']) {
      expect(getContentCategorySchema(t).safeParse(category(url)).success).toBe(true);
    }
  });

  it('reject links the backend url rule refuses, like https:host without slashes', () => {
    for (const url of ['https:abcxyz.com', 'abcxyz.com', 'ftp://host/file', 'https://']) {
      expect(messagesOf(getContentCategorySchema(t).safeParse(category(url)))).toContain(
        'url.invalid',
      );
    }
  });
});
