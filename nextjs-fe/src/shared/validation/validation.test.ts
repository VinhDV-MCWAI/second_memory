import { describe, expect, it } from 'vitest';
import { getAdminSchema, getSkillLevelSchema, getSkillSchema, getTagSchema } from './validation';
import { ValidationRules } from './validation-rules';
import { AdminRole, AdminStatus, Gender, SkillLevel } from '@/shared/enums';

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

describe('skill ledger schemas', () => {
  const skill = {
    name: 'PostgreSQL',
    category: 'database',
    description: '',
    is_public: false,
    tag_ids: [],
    level: SkillLevel.LEARNING,
    changed_on: '',
    reason: '',
  };

  it('accept a skill whose first level date is left to the server', () => {
    expect(getSkillSchema(t).safeParse(skill).success).toBe(true);
  });

  it('report blank names, the backend limits and future dates', () => {
    expect(
      messagesOf(
        getSkillSchema(t).safeParse({
          ...skill,
          name: '  ',
          category: 'x'.repeat(ValidationRules.SKILL_CATEGORY_MAX + 1),
          changed_on: '2999-01-01',
        }),
      ).sort(),
    ).toEqual(
      [
        'changedOn.future',
        `category.maxLength:{"max":${ValidationRules.SKILL_CATEGORY_MAX}}`,
        'skillName.required',
      ].sort(),
    );
  });

  it('reject a level outside the scale', () => {
    expect(messagesOf(getSkillLevelSchema(t).safeParse({ ...skill, level: 5 }))).toEqual([
      'level.required',
    ]);
  });

  it('trim tag names', () => {
    expect(getTagSchema(t).safeParse({ name: ' php ' })).toMatchObject({ data: { name: 'php' } });
    expect(messagesOf(getTagSchema(t).safeParse({ name: '' }))).toEqual(['tagName.required']);
  });
});
