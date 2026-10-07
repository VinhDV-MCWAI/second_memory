'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { SocialForm } from '@/components/forms/social-form';
import { API_ENDPOINTS } from '@/shared/api';
import { SORT_FIELDS } from '@/shared/config';
import type { SocialMgmt } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function SocialListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<SocialMgmt>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'rank_order', label: tFields('order'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    { key: 'slug', label: tFields('slug') },
    { key: 'link', label: tFields('link') },
    { key: 'status', label: tFields('status'), sortable: true },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'name', label: tFields('name'), type: 'text', placeholder: tCommon('search') },
  ];
  const searchFields: SearchField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'slug', label: tFields('slug'), type: 'text' },
    { key: 'link', label: tFields('link'), type: 'text' },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MANAGEMENT.SOCIAL}
      entity={{ one: 'social', many: 'socials' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy={SORT_FIELDS.RANK_ORDER}
      filtersKey="social-filters"
      form={SocialForm}
      dialogClassName="max-w-xl"
    />
  );
}
