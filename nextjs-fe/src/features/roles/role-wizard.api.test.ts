import { describe, expect, it } from 'vitest';
import { buildPermissionUpdate } from './role-wizard.api';

describe('buildPermissionUpdate', () => {
  it('returns null when the selection did not change', () => {
    expect(buildPermissionUpdate(7, [1, 2], [2, 1])).toBeNull();
    expect(buildPermissionUpdate(7, [], [])).toBeNull();
  });

  it('inserts new and deletes removed api ids for the role', () => {
    expect(buildPermissionUpdate(7, [1, 2], [2, 3])).toEqual({
      role_mst_id: 7,
      insert: [{ role_mst_id: 7, api_mst_id: 3 }],
      delete: [{ role_mst_id: 7, api_mst_id: 1 }],
    });
  });

  it('omits empty insert/delete lists', () => {
    expect(buildPermissionUpdate(7, [], [5])).toEqual({
      role_mst_id: 7,
      insert: [{ role_mst_id: 7, api_mst_id: 5 }],
    });
  });
});
