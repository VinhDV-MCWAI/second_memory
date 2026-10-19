'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { EndUserForm } from '@/features/master/components/end-user-form';
import { RegistrationSwitch } from '@/features/master/components/registration-switch';
import { API_ENDPOINTS } from '@/shared/api';
import { IsActive } from '@/shared/enums/enums';
import type { EndUser } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

/** End users of the public site (ADR-0020): no Add button, they sign up themselves. */
export default function EndUserListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const activeOptions = [
    { value: String(IsActive.TRUE), label: tCommon('active') },
    { value: String(IsActive.FALSE), label: tCommon('inactive') },
  ];

  const columns: Column<EndUser>[] = [
    { key: 'email', label: tFields('email'), sortable: true },
    { key: 'display_name', label: tFields('displayName'), sortable: true },
    { key: 'saved_count', label: tFields('savedCount') },
    {
      key: 'is_active',
      label: tFields('status'),
      sortable: true,
      render: (item) => (
        <StatusBadge active={item.is_active}>
          {item.is_active ? tCommon('active') : tCommon('inactive')}
        </StatusBadge>
      ),
    },
    {
      key: 'last_login_at',
      label: tFields('lastLoginAt'),
      sortable: true,
      render: (item) => item.last_login_at ?? '—',
    },
    { key: 'created_at', label: tFields('createdAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'email', label: tFields('email'), type: 'text' },
    { key: 'display_name', label: tFields('displayName'), type: 'text' },
    { key: 'is_active', label: tFields('status'), type: 'select', options: activeOptions },
  ];
  const searchFields: SearchField[] = [
    { key: 'email', label: tFields('email'), type: 'text' },
    { key: 'display_name', label: tFields('displayName'), type: 'text' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MASTER.END_USER}
      entity={{ one: 'endUser', many: 'endUsers' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="created_at"
      filtersKey="end-user-filters"
      form={EndUserForm}
      dialogSize="md"
      toolbar={<RegistrationSwitch />}
    />
  );
}
