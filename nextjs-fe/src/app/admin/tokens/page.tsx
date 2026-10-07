'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { TokenForm } from '@/features/master/components/token-form';
import { API_ENDPOINTS } from '@/shared/api';
import { SORT_FIELDS } from '@/shared/config';
import type { TokenMst } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function TokenListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<TokenMst>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'account_id', label: tFields('accountId'), sortable: true },
    { key: 'device_name', label: tFields('deviceName') },
    { key: 'ip_address', label: tFields('ipAddress') },
    { key: 'expired_at', label: tFields('expiredAt'), sortable: true },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    {
      key: 'account_id',
      label: tFields('accountId'),
      type: 'text',
      placeholder: tCommon('search'),
    },
    { key: 'device_name', label: tFields('deviceName'), type: 'text' },
  ];
  const searchFields: SearchField[] = [
    { key: 'account_id', label: tFields('accountId'), type: 'text' },
    { key: 'device_name', label: tFields('deviceName'), type: 'text' },
    { key: 'ip_address', label: tFields('ipAddress'), type: 'text' },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MASTER.TOKEN}
      entity={{ one: 'token', many: 'tokens' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy={SORT_FIELDS.CREATED_AT}
      filtersKey="token-filters"
      form={TokenForm}
      dialogClassName="max-w-xl"
    />
  );
}
