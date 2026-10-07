'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { CategoryForm } from '@/components/forms/category-form';
import { API_ENDPOINTS } from '@/shared/api';
import { enumOptions } from '@/shared/utils/enum-options';
import { IsActive, IsActiveLabels } from '@/shared/enums/enums';
import type { CategoryMgmt } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function CategoryListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<CategoryMgmt>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'rank_order', label: tFields('order'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    { key: 'description', label: tFields('description') },
    { key: 'icon', label: tFields('icon') },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (category) => (
        <StatusBadge active={category.status === IsActive.TRUE}>
          {IsActiveLabels[category.status as IsActive]}
        </StatusBadge>
      ),
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'name', label: tFields('name'), type: 'text', placeholder: tCommon('search') },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(IsActiveLabels),
    },
    { key: 'is_active', label: tCommon('active'), type: 'boolean' },
  ];
  const searchFields: SearchField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'description', label: tFields('description'), type: 'text' },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(IsActiveLabels),
    },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MANAGEMENT.CATEGORY}
      entity={{ one: 'category', many: 'categories' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="rank_order"
      filtersKey="category-filters"
      form={CategoryForm}
      dialogClassName="max-w-4xl"
    />
  );
}
