'use client';

import { useTranslations } from 'next-intl';
import { MoveButtons } from '@/features/content/components/move-buttons';
import { useApiData } from '@/shared/hooks/use-api-data';
import { API_ENDPOINTS } from '@/shared/api';
import type { Article } from '@/shared/types/models';

/** A book's chapters fit on one page. */
const SUB_PAGE_PAGE_SIZE = 100;

/** The sub-pages of an article in their book order, with ↑ ↓ to change it (ADR-0022). */
export function SubPages({ articleId }: { articleId: number }) {
  const t = useTranslations('content');
  const { data: children } = useApiData<Article>(API_ENDPOINTS.CONTENT.ARTICLE, {
    per_page: SUB_PAGE_PAGE_SIZE,
    filters: { parent_id: articleId },
  });

  return (
    <div className="space-y-2">
      <p className="text-sm font-medium">{t('subPages')}</p>
      <p className="text-xs text-muted-foreground">{t('subPagesHint')}</p>
      {children.length === 0 ? (
        <p className="text-sm text-muted-foreground">{t('noSubPages')}</p>
      ) : (
        <ol className="divide-y rounded-md border">
          {children.map((child, index) => (
            <li key={child.id} className="flex items-center justify-between px-3 py-1.5 text-sm">
              <span className="truncate">{child.title}</span>
              <MoveButtons
                endpoint={API_ENDPOINTS.CONTENT.ARTICLE}
                id={child.id}
                first={index === 0}
                last={index === children.length - 1}
              />
            </li>
          ))}
        </ol>
      )}
    </div>
  );
}
