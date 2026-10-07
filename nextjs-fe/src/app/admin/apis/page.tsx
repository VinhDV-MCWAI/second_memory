'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { ApiForm } from '@/features/master/components/api-form';
import { API_ENDPOINTS } from '@/shared/api';
import { enumOptions } from '@/shared/utils/enum-options';
import { TypeOfMethod, TypeOfMethodLabels } from '@/shared/enums';
import { SORT_FIELDS } from '@/shared/config';
import { IsActive, IsActiveLabels } from '@/shared/enums/enums';
import type { ApiMst } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function ApiListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<ApiMst>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    { key: 'path', label: tFields('path'), sortable: true },
    {
      key: 'type',
      label: tFields('method'),
      sortable: true,
      render: (item) => TypeOfMethodLabels[item.type as TypeOfMethod] || tCommon('unknown'),
    },
    {
      key: 'is_active',
      label: tFields('status'),
      sortable: true,
      render: (item) => (
        <StatusBadge active={item.is_active}>
          {IsActiveLabels[item.is_active ? IsActive.TRUE : IsActive.FALSE]}
        </StatusBadge>
      ),
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'name', label: tFields('name'), type: 'text', placeholder: tCommon('search') },
    { key: 'path', label: tFields('path'), type: 'text', placeholder: tCommon('search') },
    { key: 'is_active', label: tCommon('active'), type: 'boolean' },
  ];
  const searchFields: SearchField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'path', label: tFields('path'), type: 'text' },
    {
      key: 'type',
      label: tFields('method'),
      type: 'select',
      options: enumOptions(TypeOfMethodLabels),
    },
    { key: SORT_FIELDS.CREATED_AT, label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MASTER.API}
      entity={{ one: 'api', many: 'apis' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy={SORT_FIELDS.CREATED_AT}
      filtersKey="api-filters"
      form={ApiForm}
      dialogClassName="max-w-xl"
    />
  );
}
