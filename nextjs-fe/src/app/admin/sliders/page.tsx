'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { ImageCell, StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { SliderForm } from '@/components/forms/slider-form';
import { API_ENDPOINTS } from '@/shared/api';
import { enumOptions } from '@/shared/utils/enum-options';
import { IsActive, IsActiveLabels } from '@/shared/enums/enums';
import type { SliderMgmt } from '@/shared/types/api';
import type { SearchField } from '@/shared/types/data-table.types';

export default function SliderListPage() {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');

  const columns: Column<SliderMgmt>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    {
      key: 'image_url',
      label: tFields('image'),
      render: (slider) => <ImageCell src={slider.image} alt={slider.title} />,
    },
    { key: 'title', label: tFields('title'), sortable: true },
    {
      key: 'link_url',
      label: tFields('link'),
      render: (slider) =>
        slider.link ? (
          <a
            href={slider.link}
            target="_blank"
            rel="noopener noreferrer"
            className="block max-w-xs truncate text-blue-600 hover:underline"
          >
            {slider.link}
          </a>
        ) : (
          <span className="text-gray-400">-</span>
        ),
    },
    { key: 'rank_order', label: tFields('order'), sortable: true },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (slider) => (
        <StatusBadge active={slider.status === IsActive.TRUE}>
          {slider.status === IsActive.TRUE ? tCommon('active') : tCommon('inactive')}
        </StatusBadge>
      ),
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    {
      key: 'title',
      label: tFields('title'),
      type: 'text',
      placeholder: tCommon('search'),
    },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(IsActiveLabels),
    },
  ];
  const searchFields: SearchField[] = [
    { key: 'title', label: tFields('title'), type: 'text' },
    { key: 'link_url', label: tFields('link'), type: 'text' },
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
      endpoint={API_ENDPOINTS.MANAGEMENT.SLIDER}
      entity={{ one: 'slider', many: 'sliders' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="order"
      filtersKey="slider-filters"
      form={SliderForm}
      dialogClassName="max-w-4xl"
    />
  );
}
