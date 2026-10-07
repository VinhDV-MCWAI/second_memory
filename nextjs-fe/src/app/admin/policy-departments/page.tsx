'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { PolicyDepartmentForm } from '@/components/forms/policy-department-form';
import { API_ENDPOINTS } from '@/shared/api';
import { SORT_FIELDS } from '@/shared/config';
import type { PolicyDepartmentMst } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function PolicyDepartmentListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<PolicyDepartmentMst>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'table_name', label: tFields('tableName'), sortable: true },
    { key: 'row_id', label: tFields('rowId'), sortable: true },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    {
      key: 'table_name',
      label: tFields('tableName'),
      type: 'text',
      placeholder: tCommon('search'),
    },
  ];
  const searchFields: SearchField[] = [
    { key: 'table_name', label: tFields('tableName'), type: 'text' },
    { key: 'row_id', label: tFields('rowId'), type: 'number' },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MASTER.POLICY_DEPARTMENT}
      entity={{ one: 'policyDepartment', many: 'policyDepartments' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy={SORT_FIELDS.CREATED_AT}
      filtersKey="policy-department-filters"
      form={PolicyDepartmentForm}
      dialogClassName="max-w-xl"
    />
  );
}
