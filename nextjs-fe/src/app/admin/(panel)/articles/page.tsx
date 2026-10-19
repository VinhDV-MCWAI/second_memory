'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { ArticleForm } from '@/features/content/components/article-form';
import { API_ENDPOINTS } from '@/shared/api';
import { SORT_ORDER } from '@/shared/config';
import { formatTimestamp } from '@/shared/utils/date-formatter';
import type { Article } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

/** Articles of the public site (ADR-0021). */
export default function ArticleListPage() {
  const tFields = useTranslations('fields');
  const tLedger = useTranslations('ledger');

  const columns: Column<Article>[] = [
    { key: 'title', label: tFields('title'), sortable: true },
    { key: 'category_name', label: tFields('category') },
    { key: 'sort_order', label: tFields('sortOrder'), sortable: true },
    {
      key: 'is_public',
      label: tFields('visibility'),
      render: (item) => (
        <StatusBadge active={item.is_public}>
          {item.is_public ? tLedger('public') : tLedger('private')}
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
    { key: 'is_public', label: tLedger('public'), type: 'boolean' },
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
