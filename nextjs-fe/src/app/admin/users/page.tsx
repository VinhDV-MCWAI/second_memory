'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { UserForm } from '@/components/forms/user-form';
import { API_ENDPOINTS } from '@/shared/api';
import { enumOptions } from '@/shared/utils/enum-options';
import { Gender, GenderLabels, UserStatus, UserStatusLabels } from '@/shared/enums/enums';
import type { UserMgmt } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function UsersPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<UserMgmt>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'user_name', label: tFields('username'), sortable: true },
    {
      key: 'first_name',
      label: tFields('name'),
      sortable: true,
      render: (user) => `${user.first_name} ${user.last_name}`,
    },
    { key: 'email', label: tFields('email'), sortable: true },
    {
      key: 'gender',
      label: tFields('gender'),
      render: (user) => GenderLabels[user.gender as Gender] || tCommon('unknown'),
    },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (user) => (
        <StatusBadge active={user.status === UserStatus.ACTIVE}>
          {UserStatusLabels[user.status as UserStatus] || tCommon('unknown')}
        </StatusBadge>
      ),
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'user_name', label: tFields('username'), type: 'text' },
    { key: 'email', label: tFields('email'), type: 'text' },
    { key: 'first_name', label: tFields('firstName'), type: 'text' },
    {
      key: 'gender',
      label: tFields('gender'),
      type: 'select',
      options: enumOptions(GenderLabels),
    },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(UserStatusLabels),
    },
  ];

  const searchFields: SearchField[] = [
    { key: 'user_name', label: tFields('username'), type: 'text' },
    { key: 'email', label: tFields('email'), type: 'text' },
    { key: 'first_name', label: tFields('name'), type: 'text' },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(UserStatusLabels),
    },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MANAGEMENT.USER}
      entity={{ one: 'user', many: 'users' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="created_at"
      filtersKey="user-filters"
      form={UserForm}
      dialogClassName="max-w-4xl"
    />
  );
}
