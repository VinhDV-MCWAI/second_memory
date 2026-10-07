'use client';

import { useTranslations } from 'next-intl';
import { Calendar, History, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useApiData } from '@/shared/hooks/use-api-data';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { AuditEvent } from '@/shared/enums';
import { SORT_ORDER } from '@/shared/config';
import type { AuditLogEntry } from '@/shared/types/models';
import type { HistoryViewerProps } from '@/shared/types/data-table.types';

const EVENT_CLASS: Partial<Record<AuditEvent, string>> = {
  [AuditEvent.CREATED]: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  [AuditEvent.UPDATED]: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  [AuditEvent.DELETED]: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

const show = (value: unknown): string =>
  value === null || value === undefined || value === '' ? '—' : String(value);

/** Field-level changes of one entry: `field: old → new` (only new or only old for create/delete). */
function Changes({ entry }: { entry: AuditLogEntry }) {
  const fields = Object.keys({ ...entry.old_values, ...entry.new_values });
  if (fields.length === 0) return null;

  return (
    <ul className="mt-2 space-y-1 text-sm">
      {fields.map((field) => (
        <li key={field}>
          <span className="font-medium">{field}</span>:{' '}
          {entry.old_values && (
            <span className="text-muted-foreground line-through">
              {show(entry.old_values[field])}
            </span>
          )}
          {entry.old_values && entry.new_values && ' → '}
          {entry.new_values && <span>{show(entry.new_values[field])}</span>}
        </li>
      ))}
    </ul>
  );
}

/** Newest-first audit trail of one record, read from the audit log (ADR-0006). */
export function HistoryViewer({ auditableType, recordId, className }: HistoryViewerProps) {
  const t = useTranslations('history');
  const { data: entries, loading } = useApiData<AuditLogEntry>(API_ENDPOINTS.AUDIT.LOG, {
    filters: { auditable_type: auditableType, auditable_id: recordId },
    sort_by: 'id',
    sort_order: SORT_ORDER.DESC,
  });

  if (loading || entries.length === 0) {
    return (
      <Card className={className}>
        <CardContent className="flex h-32 items-center justify-center">
          <div className="text-center text-muted-foreground">
            <History className={loading ? 'mx-auto h-8 w-8 animate-spin' : 'mx-auto h-8 w-8'} />
            <p className="mt-2">{loading ? t('loading') : t('noHistory')}</p>
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
          {entries.map((entry, index) => (
            <div key={entry.id}>
              <Badge className={EVENT_CLASS[entry.event as AuditEvent]}>
                {t(`events.${entry.event}`)}
              </Badge>
              <div className="mt-2 flex items-center gap-4 text-sm text-muted-foreground">
                <div className="flex items-center gap-1">
                  <User className="h-4 w-4" />
                  <span>
                    {t('by', { name: entry.actor_user_name ?? `#${show(entry.admin_mst_id)}` })}
                  </span>
                </div>
                <div className="flex items-center gap-1">
                  <Calendar className="h-4 w-4" />
                  <time dateTime={entry.created_at}>
                    {new Date(entry.created_at).toLocaleString()}
                  </time>
                </div>
              </div>
              <Changes entry={entry} />
              {index < entries.length - 1 && <Separator className="mt-4" />}
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
