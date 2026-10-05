'use client';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowRight, Plus, Minus } from 'lucide-react';
import { useTranslations } from 'next-intl';
import type { DiffViewerProps } from '@/shared/types/data-table.types';

export function DiffViewer({ diffs, className }: DiffViewerProps) {
  const t = useTranslations('history');

  const renderValue = (value: unknown) => {
    if (value === null || value === undefined) {
      return <span className="text-muted-foreground italic">{t('nullValue')}</span>;
    }
    if (typeof value === 'boolean') {
      return <Badge variant={value ? 'default' : 'secondary'}>{value.toString()}</Badge>;
    }
    if (typeof value === 'object') {
      return <code className="text-xs">{JSON.stringify(value, null, 2)}</code>;
    }
    return <span>{value.toString()}</span>;
  };

  if (diffs.length === 0) {
    return (
      <Card className={className}>
        <CardContent className="flex h-32 items-center justify-center text-muted-foreground">
          {t('noChanges')}
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle>{t('changes')}</CardTitle>
        <CardDescription>
          {t('fieldsModified', { count: diffs.length })}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {diffs.map((diff, index) => (
            <div key={index} className="rounded-lg border p-4">
              <div className="mb-2 font-medium">{diff.label || diff.field}</div>
              
              <div className="grid gap-2 md:grid-cols-[1fr,auto,1fr]">
                {/* Old Value */}
                <div className="rounded-md bg-red-50 p-3 dark:bg-red-950/20">
                  <div className="mb-1 flex items-center gap-2 text-xs font-medium text-red-700 dark:text-red-400">
                    <Minus className="h-3 w-3" />
                    {t('oldValue')}
                  </div>
                  <div className="text-sm">{renderValue(diff.oldValue)}</div>
                </div>

                {/* Arrow */}
                <div className="flex items-center justify-center">
                  <ArrowRight className="h-5 w-5 text-muted-foreground" />
                </div>

                {/* New Value */}
                <div className="rounded-md bg-green-50 p-3 dark:bg-green-950/20">
                  <div className="mb-1 flex items-center gap-2 text-xs font-medium text-green-700 dark:text-green-400">
                    <Plus className="h-3 w-3" />
                    {t('newValue')}
                  </div>
                  <div className="text-sm">{renderValue(diff.newValue)}</div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
