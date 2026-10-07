'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { EntryDescriptionForm } from '@/components/forms/entry-description-form';
import { API_ENDPOINTS } from '@/shared/api';
import { StatusEnum, StatusEnumLabels } from '@/shared/enums';
import type { EntryDescriptionMgmt } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function EntryDescriptionListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<EntryDescriptionMgmt>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (item) => (
        <span
          className={`rounded-full px-2 py-1 text-xs ${
            item.status === StatusEnum.PUBLISHED
              ? 'bg-green-100 text-green-800'
              : item.status === StatusEnum.ARCHIVED
                ? 'bg-gray-100 text-gray-800'
                : 'bg-yellow-100 text-yellow-800'
          }`}
        >
          {StatusEnumLabels[item.status as StatusEnum] || tCommon('unknown')}
        </span>
      ),
    },
    {
      key: 'title',
      label: tFields('title'),
      sortable: true,
    },
    {
      key: 'summary',
      label: tFields('summary'),
      render: (item) => (
        <div className="max-w-md truncate" title={item.summary || ''}>
          {item.summary}
        </div>
      ),
    },
    { key: 'rank_order', label: tFields('order'), sortable: true },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    {
      key: 'entry_mgmt_id',
      label: tFields('entryId'),
      type: 'text',
      placeholder: tCommon('search'),
    },
  ];
  const searchFields: SearchField[] = [
    { key: 'entry_mgmt_id', label: tFields('entryId'), type: 'text' },
    { key: 'title', label: tFields('title'), type: 'text' },
    { key: 'summary', label: tFields('summary'), type: 'text' },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MANAGEMENT.ENTRY_DESCRIPTION}
      entity={{ one: 'entryDescription', many: 'entryDescriptions' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="order"
      filtersKey="entry-description-filters"
      form={EntryDescriptionForm}
      dialogClassName="max-w-4xl"
    />
  );
}
