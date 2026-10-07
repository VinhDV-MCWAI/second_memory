import { ENDPOINTS, API_ENDPOINTS, apiClient } from '@/shared/api';
import type { RoleMst } from '@/shared/types/api';
import type { WizardState } from '@/features/roles/role-wizard.types';

type ApiRoleRow = { role_mst_id: number; api_mst_id: number };

export interface PermissionUpdate {
  role_mst_id: number;
  insert?: ApiRoleRow[];
  delete?: ApiRoleRow[];
}

/** API ids assigned to a role (the role list does not include them). */
export async function fetchAssignedApiIds(roleId: number): Promise<number[]> {
  const response = await apiClient.get<{ data: ApiRoleRow[] } | ApiRoleRow[]>(
    API_ENDPOINTS.JUNCTION.API_ROLE + '/list',
    {
      params: {
        role_mst_id: roleId,
        per_page: 9999, // Fetch all (using large number as -1 might default to 15)
      },
    },
  );

  // Paginated or plain array
  const rawData = response.data;
  const assignedList = Array.isArray(rawData) ? rawData : rawData.data || [];
  return assignedList.map((item) => item.api_mst_id);
}

/** Creates or updates the role and returns its id. */
export async function saveRole(
  roleData: WizardState['roleData'],
  existing?: RoleMst | null,
): Promise<number> {
  const rolePayload = {
    name: roleData.name,
    permission: roleData.permission,
    is_active: roleData.is_active,
    is_delete: false,
  };

  if (existing) {
    await apiClient.put<{ data: number }>(`${ENDPOINTS.MASTER.ROLE}/update/${existing.id}`, {
      id: existing.id,
      ...rolePayload,
    });
    return existing.id;
  }

  const res = await apiClient.post<number>(`${ENDPOINTS.MASTER.ROLE}/store`, rolePayload);
  return res.data;
}

/** Junction update for the selection change, or `null` when nothing changed. */
export function buildPermissionUpdate(
  roleId: number,
  initialApiIds: number[],
  currentApiIds: number[],
): PermissionUpdate | null {
  const toInsertIds = currentApiIds.filter((id) => !initialApiIds.includes(id));
  const toDeleteIds = initialApiIds.filter((id) => !currentApiIds.includes(id));

  if (toInsertIds.length === 0 && toDeleteIds.length === 0) return null;

  const toRow = (apiId: number): ApiRoleRow => ({ role_mst_id: roleId, api_mst_id: apiId });
  const update: PermissionUpdate = { role_mst_id: roleId };
  if (toInsertIds.length > 0) update.insert = toInsertIds.map(toRow);
  if (toDeleteIds.length > 0) update.delete = toDeleteIds.map(toRow);
  return update;
}

export async function savePermissions(update: PermissionUpdate): Promise<void> {
  await apiClient.put(API_ENDPOINTS.JUNCTION.API_ROLE + '/update', update);
}
