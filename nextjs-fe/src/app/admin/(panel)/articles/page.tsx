'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { ArticleForm } from '@/features/content/components/article-form';
import { useApiData } from '@/shared/hooks/use-api-data';
import { API_ENDPOINTS } from '@/shared/api';
import { SORT_ORDER } from '@/shared/config';
import { formatTimestamp } from '@/shared/utils/date-formatter';
import type { Article, ContentCategory } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

/** Every category fits the filter's select. */
const CATEGORY_PAGE_SIZE = 100;

/** Articles (ADR-0022): newest change first, with their categories and their place in a book. */
export default function ArticleListPage() {
  const tFields = useTranslations('fields');
  const tContent = useTranslations('content');
  const { data: categories } = useApiData<ContentCategory>(API_ENDPOINTS.CONTENT.CATEGORY, {
    per_page: CATEGORY_PAGE_SIZE,
  });

  const columns: Column<Article>[] = [
    {
      key: 'title',
      label: tFields('title'),
      sortable: true,
      render: (item) => (
        <span>
          <span className="block">{item.title}</span>
          {item.parent_title && (
            <span className="block text-xs text-muted-foreground">↳ {item.parent_title}</span>
          )}
        </span>
      ),
    },
    {
      key: 'categories',
      label: tFields('categories'),
      render: (item) => item.categories.map((category) => category.name).join(', '),
    },
    { key: 'children_count', label: tFields('subPages') },
    {
      key: 'is_public',
      label: tFields('visibility'),
      render: (item) => (
        <StatusBadge active={item.is_public}>
          {item.is_public ? tContent('public') : tContent('private')}
        </StatusBadge>
      ),
    },
    {
      key: 'updated_at',
      label: tFields('updatedAt'),
      sortable: true,
      render: (item) => formatTimestamp(item.updated_at),
    },
  ];

  const filterFields: FilterField[] = [
    { key: 'title', label: tFields('title'), type: 'text' },
    {
      key: 'category_id',
      label: tFields('category'),
      type: 'select',
      options: categories.map((category) => ({ value: category.id, label: category.name })),
    },
    { key: 'is_public', label: tContent('public'), type: 'boolean' },
  ];
  const searchFields: SearchField[] = [{ key: 'title', label: tFields('title'), type: 'text' }];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.CONTENT.ARTICLE}
      entity={{ one: 'article', many: 'articles' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="updated_at"
      defaultSortOrder={SORT_ORDER.DESC}
      filtersKey="article-filters"
      form={ArticleForm}
      dialogSize="xl"
    />
  );
}
