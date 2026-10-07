import { describe, expect, it } from 'vitest';
import { getAdminSchema } from './validation';
import { ValidationRules } from './validation-rules';
import { AdminRole, AdminStatus, Gender } from '@/shared/enums';

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
  role: AdminRole.VIEWER,
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

  it('accept the two roles only', () => {
    const valid = { ...person, status: AdminStatus.ACTIVE, password: '' };
    expect(getAdminSchema(t).safeParse({ ...valid, role: AdminRole.OWNER }).success).toBe(true);
    expect(getAdminSchema(t).safeParse({ ...valid, role: 'admin' }).success).toBe(false);
  });

  it('reject an unknown status', () => {
    expect(getAdminSchema(t).safeParse({ ...person, status: 99 }).success).toBe(false);
  });
});
