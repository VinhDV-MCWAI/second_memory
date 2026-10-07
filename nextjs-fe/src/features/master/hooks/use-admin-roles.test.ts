import { describe, expect, it } from 'vitest';
import { diffAdminRoles } from './use-admin-roles';

describe('diffAdminRoles', () => {
  it('inserts added and deletes removed roles', () => {
    expect(diffAdminRoles(4, [1, 2], [2, 3])).toEqual({
      insert: [{ admin_mst_id: 4, role_mst_id: 3 }],
      delete: [{ admin_mst_id: 4, role_mst_id: 1 }],
    });
  });

  it('is empty when nothing changed', () => {
    expect(diffAdminRoles(4, [1, 2], [2, 1])).toEqual({ insert: [], delete: [] });
  });
});
