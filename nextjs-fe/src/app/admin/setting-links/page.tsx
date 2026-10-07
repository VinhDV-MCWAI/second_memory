'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { SettingLinkForm } from '@/features/management/components/setting-link-form';
import { API_ENDPOINTS } from '@/shared/api';
import { SORT_FIELDS } from '@/shared/config';
import type { SettingLinkMgmt } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function SettingLinkListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<SettingLinkMgmt>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'key', label: tFields('key'), sortable: true },
    { key: 'value', label: tFields('value'), sortable: true },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'key', label: tFields('key'), type: 'text', placeholder: tCommon('search') },
    { key: 'value', label: tFields('value'), type: 'text', placeholder: tCommon('search') },
  ];
  const searchFields: SearchField[] = [
    { key: 'key', label: tFields('key'), type: 'text' },
    { key: 'value', label: tFields('value'), type: 'text' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MANAGEMENT.SETTING_LINK}
      entity={{ one: 'settingLink', many: 'settingLinks' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy={SORT_FIELDS.ID}
      filtersKey="setting-link-filters"
      form={SettingLinkForm}
      dialogClassName="max-w-xl"
    />
  );
}
