'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { FeatureForm } from '@/components/forms/feature-form';
import { API_ENDPOINTS } from '@/shared/api';
import { SORT_FIELDS } from '@/shared/config';
import { enumOptions } from '@/shared/utils/enum-options';
import { FeatureStatus, FeatureStatusLabels } from '@/shared/enums/enums';
import type { FeatureMst } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function FeatureListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<FeatureMst>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (feature) => (
        <StatusBadge active={feature.status === FeatureStatus.ACTIVE}>
          {FeatureStatusLabels[feature.status as FeatureStatus] || tCommon('inactive')}
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
      options: enumOptions(FeatureStatusLabels),
    },
  ];

  const searchFields: SearchField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(FeatureStatusLabels),
    },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.MASTER.FEATURE}
      entity={{ one: 'feature', many: 'features' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy={SORT_FIELDS.CREATED_AT}
      filtersKey="feature-filters"
      form={FeatureForm}
      dialogClassName="max-w-2xl"
    />
  );
}
