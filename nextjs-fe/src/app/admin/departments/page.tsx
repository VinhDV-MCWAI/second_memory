'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { DepartmentForm } from '@/components/forms/department-form';
import { API_ENDPOINTS } from '@/shared/api';
import { enumOptions } from '@/shared/utils/enum-options';
import { DepartmentStatus, DepartmentStatusLabels } from '@/shared/enums/enums';
import type { DepartmentMst } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function DepartmentListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<DepartmentMst>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'code', label: tFields('code'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (dept) => {
        return (
          <StatusBadge active={dept.status === DepartmentStatus.ACTIVE}>
            {DepartmentStatusLabels[dept.status as DepartmentStatus] || dept.status}
          </StatusBadge>
        );
      },
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'name', label: tFields('name'), type: 'text', placeholder: tCommon('search') },
    { key: 'code', label: tFields('code'), type: 'text', placeholder: tCommon('search') },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(DepartmentStatusLabels),
    },
  ];

  const searchFields: SearchField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'code', label: tFields('code'), type: 'text' },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(DepartmentStatusLabels),
    },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MASTER.DEPARTMENT}
      entity={{ one: 'department', many: 'departments' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="created_at"
      filtersKey="department-filters"
      form={DepartmentForm}
      dialogClassName="max-w-4xl"
    />
  );
}
