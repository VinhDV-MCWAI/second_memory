'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { RoleForm } from '@/features/master/components/role-form';
import { API_ENDPOINTS } from '@/shared/api';
import { AdminRole } from '@/shared/enums';
import type { Role } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

export default function RoleListPage() {
  const tFields = useTranslations('fields');
  const tPermissions = useTranslations('permissions');

  const columns: Column<Role>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    {
      key: 'is_built_in',
      label: tPermissions('kind'),
      render: (role) => (role.is_built_in ? tPermissions('builtIn') : tPermissions('custom')),
    },
    {
      key: 'permissions',
      label: tFields('permissions'),
      render: (role) =>
        role.name === AdminRole.OWNER ? tPermissions('all') : role.permissions.length,
    },
    { key: 'admin_count', label: tFields('adminCount') },
  ];

  const filterFields: FilterField[] = [{ key: 'name', label: tFields('name'), type: 'text' }];
  const searchFields: SearchField[] = [{ key: 'name', label: tFields('name'), type: 'text' }];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MASTER.ROLE}
      entity={{ one: 'role', many: 'roles' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="name"
      filtersKey="role-filters"
      form={RoleForm}
      dialogClassName="sm:max-w-4xl"
    />
  );
}
