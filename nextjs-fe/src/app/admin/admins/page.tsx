'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { AdminForm } from '@/components/forms/admin-form';
import { API_ENDPOINTS } from '@/shared/api';
import { enumOptions } from '@/shared/utils/enum-options';
import { AdminStatusLabels } from '@/shared/enums';
import { SORT_FIELDS } from '@/shared/config';
import type { AdminMst } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function AdminListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<AdminMst>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'first_name', label: tFields('firstName'), sortable: true },
    { key: 'last_name', label: tFields('lastName'), sortable: true },
    { key: 'email', label: tFields('email'), sortable: true },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (item) => (
        <StatusBadge active={item.is_active}>
          {item.is_active ? tCommon('active') : tCommon('inactive')}
        </StatusBadge>
      ),
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'first_name', label: tFields('firstName'), type: 'text' },
    { key: 'email', label: tFields('email'), type: 'text' },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(AdminStatusLabels),
    },
  ];

  const searchFields: SearchField[] = [
    { key: 'first_name', label: tFields('firstName'), type: 'text' },
    { key: 'email', label: tFields('email'), type: 'text' },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(AdminStatusLabels),
    },
    { key: SORT_FIELDS.CREATED_AT, label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MASTER.ADMIN}
      entity={{ one: 'admin', many: 'admins' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="created_at"
      filtersKey="admin-filters"
      form={AdminForm}
      dialogClassName="max-w-2xl"
    />
  );
}
