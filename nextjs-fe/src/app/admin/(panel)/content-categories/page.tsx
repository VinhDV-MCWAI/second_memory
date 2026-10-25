'use client';

import { ResourceListPage } from '@/components/common/resource-list-page';
import { CategoryTreeView } from '@/features/content/components/category-tree-view';
import { ContentCategoryForm } from '@/features/content/components/content-category-form';
import { API_ENDPOINTS } from '@/shared/api';
import type { ContentCategory } from '@/shared/types/models';

/**
 * Groups and their categories in the order visitors see them (ADR-0022), as a tree that folds,
 * searches and reorders by drag and drop (ADR-0023).
 */
export default function ContentCategoryListPage() {
  return (
    <ResourceListPage<ContentCategory>
      endpoint={API_ENDPOINTS.CONTENT.CATEGORY}
      entity={{ one: 'contentCategory', many: 'contentCategories' }}
      view={(actions) => <CategoryTreeView {...actions} />}
      form={ContentCategoryForm}
      dialogSize="lg"
    />
  );
}
