'use client';

import { useTranslations } from 'next-intl';
import { Calendar, History, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useApiData } from '@/shared/hooks/useApiData';
import { ActionType } from '@/shared/enums';
import { SORT_ORDER } from '@/shared/config/constant';
import type { HistoryRecord } from '@/shared/types/models';
import type { HistoryViewerProps } from '@/shared/types/data-table.types';

const ACTIONS: Record<ActionType, { key: 'create' | 'update' | 'delete'; className: string }> = {
  [ActionType.CREATE]: {
    key: 'create',
    className: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  },
  [ActionType.UPDATE]: {
    key: 'update',
    className: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  },
  [ActionType.DELETE]: {
    key: 'delete',
    className: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  },
};

/** Newest-first audit trail of one record, read from its `*-hist` list endpoint. */
export function HistoryViewer({ endpoint, foreignKey, recordId, className }: HistoryViewerProps) {
  const t = useTranslations('history');
  const { data: history, loading } = useApiData<HistoryRecord>(endpoint, {
    filters: { [foreignKey]: recordId },
    sort_by: 'id',
    sort_order: SORT_ORDER.DESC,
  });

  if (loading) {
    return (
      <Card className={className}>
        <CardContent className="flex h-64 items-center justify-center">
          <div className="text-center text-muted-foreground">
            <History className="mx-auto h-8 w-8 animate-spin" />
            <p className="mt-2">{t('loading')}</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (history.length === 0) {
    return (
      <Card className={className}>
        <CardContent className="flex h-64 items-center justify-center">
          <div className="text-center text-muted-foreground">
            <History className="mx-auto h-8 w-8" />
            <p className="mt-2">{t('noHistory')}</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <History className="h-5 w-5" />
          {t('title')}
        </CardTitle>
        <CardDescription>{t('description')}</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {history.map((item, index) => {
            const action = ACTIONS[item.action as ActionType];
            return (
              <div key={item.id}>
                <div className="flex items-center gap-2">
                  <Badge className={action?.className}>
                    {action ? t(`actions.${action.key}`) : item.action}
                  </Badge>
                </div>
                <div className="mt-2 flex items-center gap-4 text-sm text-muted-foreground">
                  <div className="flex items-center gap-1">
                    <User className="h-4 w-4" />
                    <span>{t('user', { id: item.author_id })}</span>
                  </div>
                  <div className="flex items-center gap-1">
                    <Calendar className="h-4 w-4" />
                    <span>{item.created_at}</span>
                  </div>
                </div>
                {index < history.length - 1 && <Separator className="mt-4" />}
              </div>
            );
          })}
        </div>
      </CardContent>
    </Card>
  );
}
