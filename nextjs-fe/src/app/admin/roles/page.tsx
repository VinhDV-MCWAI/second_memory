'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { RoleWizardDialog } from '@/components/forms/role-wizard-dialog';
import { API_ENDPOINTS } from '@/shared/api';
import { SORT_FIELDS } from '@/shared/config';
import type { RoleMst } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function RoleListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<RoleMst>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    { key: 'description', label: tFields('description') },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (role) => (
        <StatusBadge active={role.is_active}>
          {role.is_active ? tCommon('active') : tCommon('inactive')}
        </StatusBadge>
      ),
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'is_active', label: tFields('status'), type: 'boolean' },
  ];

  const searchFields: SearchField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'description', label: tFields('description'), type: 'text' },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MASTER.ROLE}
      entity={{ one: 'role', many: 'roles' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy={SORT_FIELDS.CREATED_AT}
      filtersKey="role-filters"
      editor={RoleWizardDialog}
    />
  );
}
