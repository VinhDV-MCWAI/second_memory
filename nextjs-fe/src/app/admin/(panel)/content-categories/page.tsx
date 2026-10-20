'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { ContentCategoryForm } from '@/features/content/components/content-category-form';
import { API_ENDPOINTS } from '@/shared/api';
import type { ContentCategory } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

/** Categories of the public home page, in the order visitors see them (ADR-0021). */
export default function ContentCategoryListPage() {
  const tFields = useTranslations('fields');
  const tContent = useTranslations('content');

  const columns: Column<ContentCategory>[] = [
    { key: 'sort_order', label: tFields('sortOrder'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    { key: 'slug', label: tFields('slug') },
    { key: 'article_count', label: tFields('articleCount') },
    {
      key: 'is_public',
      label: tFields('visibility'),
      render: (item) => (
        <StatusBadge active={item.is_public}>
          {item.is_public ? tContent('public') : tContent('private')}
        </StatusBadge>
      ),
    },
  ];

  const filterFields: FilterField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'is_public', label: tContent('public'), type: 'boolean' },
  ];
  const searchFields: SearchField[] = [{ key: 'name', label: tFields('name'), type: 'text' }];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.CONTENT.CATEGORY}
      entity={{ one: 'contentCategory', many: 'contentCategories' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="sort_order"
      filtersKey="content-category-filters"
      form={ContentCategoryForm}
      dialogSize="md"
    />
  );
}
