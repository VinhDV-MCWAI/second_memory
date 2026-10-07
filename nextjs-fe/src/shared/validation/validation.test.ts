import { describe, expect, it } from 'vitest';
import {
  getAdminSchema,
  getApiSchema,
  getCategorySchema,
  getDepartmentSchema,
  getEntryDescriptionSchema,
  getEntrySchema,
  getFeatureSchema,
  getPolicyDepartmentSchema,
  getRoleSchema,
  getTokenSchema,
  getUserSchema,
} from './validation';
import { ValidationRules } from './validation-rules';
import { AdminStatus, Gender, UserStatus } from '@/shared/enums';

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
  is_active: true,
};

describe('admin and user schemas', () => {
  it('accept a valid record and an empty password (keep current password)', () => {
    expect(
      getAdminSchema(t).safeParse({ ...person, status: AdminStatus.ACTIVE, password: '' }).success,
    ).toBe(true);
    expect(
      getUserSchema(t).safeParse({ ...person, status: UserStatus.ACTIVE, password: null }).success,
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

  it('reject an unknown status', () => {
    expect(getUserSchema(t).safeParse({ ...person, status: 99 }).success).toBe(false);
  });
});

describe('content schemas', () => {
  it('coerce string statuses for categories and entries', () => {
    const category = getCategorySchema(t).parse({
      name: 'Docs',
      slug: 'docs',
      status: '1',
      is_display: true,
      rank_order: 0,
      is_delete: false,
    });
    expect(category.status).toBe(1);
    expect(getEntrySchema(t).parse({ name: 'Intro', rank_order: 1, status: '0' }).status).toBe(0);
  });

  it('require the visible fields', () => {
    expect(
      messagesOf(getEntryDescriptionSchema(t).safeParse({ title: '', rank_order: -1, status: 0 })),
    ).toEqual(['title.required', 'order.min:{"min":0}']);
  });
});

describe('master data schemas', () => {
  it('enforce role and department limits', () => {
    expect(
      messagesOf(
        getRoleSchema(t).safeParse({ name: 'x'.repeat(31), permission: '', is_active: true }),
      ),
    ).toEqual(['name.maxLength:{"max":30}', 'permission.required']);
    expect(
      messagesOf(getDepartmentSchema(t).safeParse({ code: '', name: 'Sales', status: 1 })),
    ).toEqual(['code.required']);
  });

  it('validate features, APIs, tokens and policies', () => {
    expect(
      messagesOf(getFeatureSchema(t).safeParse({ name: 'Users', group_name: '', status: 1 })),
    ).toEqual(['groupName.required']);
    expect(
      getApiSchema(t).safeParse({
        name: 'List users',
        path: '/admin/user-mgmt/list',
        type: 1,
        method: 'GET',
        feature_mst_id: 1,
        is_active: true,
      }).success,
    ).toBe(true);
    expect(
      messagesOf(
        getApiSchema(t).safeParse({
          name: 'x',
          path: 'y',
          type: 1,
          method: 'TRACE',
          feature_mst_id: 0,
          is_active: true,
        }),
      ),
    ).toHaveLength(2);
    expect(messagesOf(getTokenSchema(t).safeParse({ account_id: 0, device_name: '' }))).toEqual([
      'account.required',
      'deviceName.required',
    ]);
    expect(
      messagesOf(getPolicyDepartmentSchema(t).safeParse({ table_name: '', row_id: 0 })),
    ).toEqual(['tableName.required', 'rowId.required']);
  });
});
