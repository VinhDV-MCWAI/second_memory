'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { ImageCell, StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { BannerForm } from '@/components/forms/banner-form';
import { API_ENDPOINTS } from '@/shared/api';
import { enumOptions } from '@/shared/utils/enum-options';
import { TIME_CONSTANTS, SORT_FIELDS } from '@/shared/config';
import { IsActive, IsActiveLabels } from '@/shared/enums/enums';
import type { BannerMgmt } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function BannerListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<BannerMgmt>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    {
      key: 'image',
      label: tFields('image'),
      render: (banner) => <ImageCell src={banner.image} alt={banner.title} />,
    },
    { key: 'title', label: tFields('title'), sortable: true },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (banner) => (
        <StatusBadge active={banner.status === IsActive.TRUE}>
          {banner.status === IsActive.TRUE ? tCommon('active') : tCommon('inactive')}
        </StatusBadge>
      ),
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'title', label: tFields('title'), type: 'text', placeholder: tCommon('search') },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(IsActiveLabels),
    },
  ];

  const searchFields: SearchField[] = [
    { key: 'title', label: tFields('title'), type: 'text' },
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
      endpoint={API_ENDPOINTS.MANAGEMENT.BANNER}
      entity={{ one: 'banner', many: 'banners' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy={SORT_FIELDS.ID}
      filtersKey="banner-filters"
      form={BannerForm}
      dialogClassName="max-w-4xl"
      staleTime={TIME_CONSTANTS.STALE_TIME}
    />
  );
}
