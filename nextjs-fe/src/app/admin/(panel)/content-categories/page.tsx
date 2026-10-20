'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { ContentCategoryForm } from '@/features/content/components/content-category-form';
import { MoveButtons } from '@/features/content/components/move-buttons';
import { useApiData } from '@/shared/hooks/use-api-data';
import { API_ENDPOINTS } from '@/shared/api';
import type { ContentCategory } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

/** Every group and category fits on one page, so the arrows know each row's siblings. */
const TREE_PAGE_SIZE = 100;

/**
 * Groups and their categories in the order visitors see them (ADR-0022): a category sits under
 * its group; ↑ ↓ move a row among its siblings.
 */
export default function ContentCategoryListPage() {
  const tFields = useTranslations('fields');
  const tContent = useTranslations('content');
  const { data: all } = useApiData<ContentCategory>(API_ENDPOINTS.CONTENT.CATEGORY, {
    per_page: TREE_PAGE_SIZE,
  });
  const siblingsOf = (item: ContentCategory) =>
    all.filter((row) => row.parent_id === item.parent_id);

  const columns: Column<ContentCategory>[] = [
    {
      key: 'sort_order',
      label: tFields('order'),
      render: (item) => {
        const siblings = siblingsOf(item);
        return (
          <MoveButtons
            endpoint={API_ENDPOINTS.CONTENT.CATEGORY}
            id={item.id}
            first={siblings[0]?.id === item.id}
            last={siblings[siblings.length - 1]?.id === item.id}
          />
        );
      },
    },
    {
      key: 'name',
      label: tFields('name'),
      sortable: true,
      render: (item) =>
        item.parent_id === null ? (
          <span className="font-semibold">{item.name}</span>
        ) : (
          <span className="pl-6">{item.name}</span>
        ),
    },
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
      defaultSortBy="tree"
      filtersKey="content-category-filters"
      form={ContentCategoryForm}
      dialogSize="lg"
    />
  );
}
