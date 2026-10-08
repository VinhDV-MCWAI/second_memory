'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { TagForm } from '@/features/ledger/components/tag-form';
import { API_ENDPOINTS } from '@/shared/api';
import type { Tag } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

export default function TagListPage() {
  const tFields = useTranslations('fields');

  const columns: Column<Tag>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
  ];

  const filterFields: FilterField[] = [{ key: 'name', label: tFields('name'), type: 'text' }];
  const searchFields: SearchField[] = [{ key: 'name', label: tFields('name'), type: 'text' }];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.LEDGER.TAG}
      entity={{ one: 'tag', many: 'tags' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="name"
      filtersKey="tag-filters"
      form={TagForm}
      dialogClassName="max-w-md"
    />
  );
}
