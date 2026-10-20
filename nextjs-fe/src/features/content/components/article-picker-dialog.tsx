'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { AppDialog } from '@/components/common/app-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useApiData } from '@/shared/hooks/use-api-data';
import { API_ENDPOINTS } from '@/shared/api';
import type { Article } from '@/shared/types/models';

/** Rows the picker shows for one search. */
const PICKER_PAGE_SIZE = 20;

interface ArticlePickerDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  /** The article being edited: it cannot link to or embed itself. */
  excludeId?: number;
  /** `embed` writes `![[slug]]` (the whole article shows), `link` writes `[[slug]]`. */
  onPick: (article: Article, as: 'link' | 'embed') => void;
}

/** Finds an article by title to link to or embed (ADR-0022). */
export function ArticlePickerDialog({
  open,
  onOpenChange,
  excludeId,
  onPick,
}: ArticlePickerDialogProps) {
  const t = useTranslations('content.picker');
  const [title, setTitle] = useState('');
  const { data: articles } = useApiData<Article>(API_ENDPOINTS.CONTENT.ARTICLE, {
    per_page: PICKER_PAGE_SIZE,
    filters: title.trim() === '' ? {} : { title: title.trim() },
  });

  return (
    <AppDialog
      open={open}
      onOpenChange={onOpenChange}
      title={t('title')}
      description={t('description')}
      size="md"
    >
      <Input
        autoFocus
        aria-label={t('search')}
        placeholder={t('search')}
        value={title}
        onChange={(event) => setTitle(event.target.value)}
      />
      <ul className="mt-3 divide-y rounded-md border">
        {articles
          .filter((article) => article.id !== excludeId)
          .map((article) => (
            <li key={article.id} className="flex items-center gap-2 px-3 py-1.5 text-sm">
              <span className="min-w-0 flex-1">
                <span className="block truncate">{article.title}</span>
                <span className="block truncate font-mono text-xs text-muted-foreground">
                  {article.slug}
                </span>
              </span>
              <Button
                type="button"
                size="sm"
                variant="ghost"
                onClick={() => onPick(article, 'link')}
              >
                {t('link')}
              </Button>
              <Button
                type="button"
                size="sm"
                variant="outline"
                onClick={() => onPick(article, 'embed')}
              >
                {t('embed')}
              </Button>
            </li>
          ))}
      </ul>
    </AppDialog>
  );
}
