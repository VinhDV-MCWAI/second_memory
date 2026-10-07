import { useCallback, useEffect, useState } from 'react';
import { apiClient } from '@/shared/api/client';
import { ENDPOINTS } from '@/shared/api';
import { useApiData } from '@/shared/hooks/use-api-data';
import type { RoleMst } from '@/shared/types/api';

type AdminRoleRow = { admin_mst_id: number; role_mst_id: number };

/** Junction rows to insert/delete for a role selection change. */
export function diffAdminRoles(
  adminId: number,
  initialRoleIds: number[],
  currentRoleIds: number[],
) {
  const toRow = (roleId: number): AdminRoleRow => ({ admin_mst_id: adminId, role_mst_id: roleId });
  return {
    insert: currentRoleIds.filter((id) => !initialRoleIds.includes(id)).map(toRow),
    delete: initialRoleIds.filter((id) => !currentRoleIds.includes(id)).map(toRow),
  };
}

/**
 * Role options, the admin's assigned roles (edit mode) and the current selection.
 * `saveRoles` writes only the difference to the admin-role junction.
 */
export function useAdminRoles(adminId?: number) {
  const isEdit = !!adminId;

  const { data: roles } = useApiData<RoleMst>(ENDPOINTS.MASTER.ROLE, {
    page: 1,
    per_page: 100,
    sort_by: 'created_at',
    sort_order: 'desc',
    staleTime: 0,
    refetchOnMount: 'always',
  });

  const { data: assignedRoles } = useApiData<AdminRoleRow>(ENDPOINTS.JUNCTION.ADMIN_ROLE, {
    filters: { admin_mst_id: adminId },
    enabled: isEdit,
    staleTime: 0,
    refetchOnMount: 'always',
  });

  const [selectedRoleIds, setSelectedRoleIds] = useState<(string | number)[]>([]);
  const [initialRoleIds, setInitialRoleIds] = useState<number[]>([]);

  // Initialize selected roles when data is fetched
  useEffect(() => {
    if (assignedRoles && isEdit) {
      const roleIds = assignedRoles.map((item) => item.role_mst_id);

      // Use JSON.stringify for array comparison to prevent infinite loops
      // caused by unstable object references from useApiData
      if (JSON.stringify(roleIds) !== JSON.stringify(initialRoleIds)) {
        setSelectedRoleIds(roleIds);
        setInitialRoleIds(roleIds);
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [assignedRoles, isEdit]); // initialRoleIds excluded on purpose: the comparison guards the update

  // Stable: the form calls it from its reset effect
  const resetRoles = useCallback(() => {
    setSelectedRoleIds([]);
    setInitialRoleIds([]);
  }, []);

  const saveRoles = async (savedAdminId: number) => {
    const currentRoleIds = selectedRoleIds.map(Number);

    if (isEdit) {
      const diff = diffAdminRoles(savedAdminId, initialRoleIds, currentRoleIds);
      if (diff.insert.length > 0 || diff.delete.length > 0) {
        await apiClient.put(`${ENDPOINTS.JUNCTION.ADMIN_ROLE}/update`, {
          insert: diff.insert.length > 0 ? diff.insert : undefined,
          delete: diff.delete.length > 0 ? diff.delete : undefined,
        });
        setInitialRoleIds(currentRoleIds);
      }
    } else if (currentRoleIds.length > 0) {
      await apiClient.put(`${ENDPOINTS.JUNCTION.ADMIN_ROLE}/update`, {
        insert: diffAdminRoles(savedAdminId, [], currentRoleIds).insert,
      });
    }
  };

  const roleOptions = roles.map((role) => ({ value: role.id, label: role.name }));

  return { roleOptions, selectedRoleIds, setSelectedRoleIds, resetRoles, saveRoles };
}
