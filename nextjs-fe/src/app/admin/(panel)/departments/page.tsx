'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { DepartmentForm } from '@/features/master/components/department-form';
import { API_ENDPOINTS } from '@/shared/api';
import { IsActive } from '@/shared/enums/enums';
import type { Department } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';
import { formatDateTime } from '@/shared/utils/date-formatter';

export default function DepartmentListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const activeOptions = [
    { value: String(IsActive.TRUE), label: tCommon('active') },
    { value: String(IsActive.FALSE), label: tCommon('inactive') },
  ];

  const columns: Column<Department>[] = [
    { key: 'code', label: tFields('code'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    { key: 'description', label: tFields('description') },
    { key: 'admin_count', label: tFields('adminCount') },
    {
      key: 'is_active',
      label: tFields('status'),
      sortable: true,
      render: (item) => (
        <StatusBadge active={item.is_active}>
          {item.is_active ? tCommon('active') : tCommon('inactive')}
        </StatusBadge>
      ),
    },
    {
      key: 'updated_at',
      label: tFields('updatedAt'),
      sortable: true,
      render: (item) => formatDateTime(item.updated_at),
    },
  ];

  const filterFields: FilterField[] = [
    { key: 'code', label: tFields('code'), type: 'text' },
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'is_active', label: tFields('status'), type: 'select', options: activeOptions },
  ];
  const searchFields: SearchField[] = [
    { key: 'code', label: tFields('code'), type: 'text' },
    { key: 'name', label: tFields('name'), type: 'text' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MASTER.DEPARTMENT}
      entity={{ one: 'department', many: 'departments' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="code"
      filtersKey="department-filters"
      form={DepartmentForm}
      dialogSize="md"
    />
  );
}
