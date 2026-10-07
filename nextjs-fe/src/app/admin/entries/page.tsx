'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { EntryForm } from '@/components/forms/entry-form';
import { API_ENDPOINTS } from '@/shared/api';
import { IsDisplay, IsDisplayLabels } from '@/shared/enums/enums';
import type { EntryMgmt } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function EntryListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<EntryMgmt>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'rank_order', label: tFields('order'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    { key: 'slug', label: tFields('slug') },
    {
      key: 'is_display',
      label: tCommon('isDisplay'),
      sortable: true,
      render: (entry) => (
        <StatusBadge active={entry.is_display}>
          {IsDisplayLabels[entry.is_display ? IsDisplay.TRUE : IsDisplay.FALSE]}
        </StatusBadge>
      ),
    },
    { key: 'status', label: tFields('status'), sortable: true },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'name', label: tFields('name'), type: 'text', placeholder: tCommon('search') },
  ];
  const searchFields: SearchField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'slug', label: tFields('slug'), type: 'text' },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MANAGEMENT.ENTRY}
      entity={{ one: 'entry', many: 'entries' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="order"
      filtersKey="entry-filters"
      form={EntryForm}
      dialogClassName="max-w-2xl"
    />
  );
}
