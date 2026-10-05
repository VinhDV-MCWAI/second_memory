'use client';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Clock } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';
import { useTranslations } from 'next-intl';
import type { TimelineProps } from '@/shared/types/data-table.types';

export function Timeline({ history, className }: TimelineProps) {
  const t = useTranslations('history');
  const getActionColor = (action: string) => {
    switch (action) {
      case 'create':
        return 'bg-green-500 dark:bg-green-600';
      case 'update':
        return 'bg-blue-500 dark:bg-blue-600';
      case 'delete':
        return 'bg-red-500 dark:bg-red-600';
      default:
        return 'bg-gray-500 dark:bg-gray-600';
    }
  };

  const getActionLabel = (action: string) => {
    return action.charAt(0).toUpperCase() + action.slice(1);
  };

  if (history.length === 0) {
    return (
      <Card className={className}>
        <CardContent className="flex h-32 items-center justify-center text-muted-foreground">
          {t('noTimeline')}
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Clock className="h-5 w-5" />
          {t('timeline')}
        </CardTitle>
        <CardDescription>
          {t('timelineDescription')}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="relative space-y-6 pl-6">
          {/* Timeline line */}
          <div className="absolute left-[11px] top-2 h-[calc(100%-1rem)] w-0.5 bg-border" />

          {history.map((item) => (
            <div key={item.id} className="relative">
              {/* Timeline dot */}
              <div
                className={`absolute -left-6 mt-1.5 h-3 w-3 rounded-full border-2 border-background ${getActionColor(
                  item.action
                )}`}
              />

              <div className="space-y-2">
                <div className="flex items-center gap-2">
                  <Badge variant="outline">{getActionLabel(item.action)}</Badge>
                  <span className="text-sm text-muted-foreground">
                    {formatDistanceToNow(new Date(item.changed_at), { addSuffix: true })}
                  </span>
                </div>

                <div className="text-sm">
                  <div className="font-medium">{t('user', { id: item.changed_by })}</div>
                  <div className="text-muted-foreground">
                    {new Date(item.changed_at).toLocaleString()}
                  </div>
                </div>

                {item.ip_address && (
                  <div className="text-xs text-muted-foreground">
                    {t('ip')}: {item.ip_address}
                  </div>
                )}

                {/* Show changed fields count */}
                {item.new_values && (
                  <div className="text-xs text-muted-foreground">
                    {t('fieldsChanged', { count: Object.keys(item.new_values).length })}
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
