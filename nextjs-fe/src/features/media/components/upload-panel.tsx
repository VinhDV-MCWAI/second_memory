'use client';

import { useTranslations } from 'next-intl';
import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/shared/utils';
import type { UploadItem } from '../hooks/use-uploads';
import { formatBytes } from '../lib/media-paths';

interface UploadPanelProps {
  items: UploadItem[];
  onCancel: (key: string) => void;
  onClearFinished: () => void;
}

/** Uploads of this visit: progress, processing in the background, result, cancel. */
export function UploadPanel({ items, onCancel, onClearFinished }: UploadPanelProps) {
  const t = useTranslations('media.uploads');
  if (items.length === 0) return null;

  return (
    <Card className="p-4">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-sm font-semibold">{t('title')}</h3>
        <Button variant="ghost" size="sm" onClick={onClearFinished}>
          {t('clear')}
        </Button>
      </div>
      <ul className="space-y-3">
        {items.map((item) => {
          const percent = item.size === 0 ? 100 : Math.round((item.loaded / item.size) * 100);
          const active = item.state === 'waiting' || item.state === 'uploading';
          return (
            <li key={item.key} className="text-sm">
              <div className="flex items-center gap-2">
                <span className="min-w-0 flex-1 truncate font-medium" title={item.name}>
                  {item.name}
                </span>
                <span
                  className={cn(
                    'shrink-0 text-xs',
                    item.state === 'failed' ? 'text-red-600' : 'text-muted-foreground',
                  )}
                >
                  {item.state === 'uploading'
                    ? `${percent}% · ${formatBytes(item.loaded)} / ${formatBytes(item.size)}`
                    : t(`states.${item.state}`)}
                </span>
                {active && (
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6"
                    aria-label={t('cancel')}
                    onClick={() => onCancel(item.key)}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                )}
              </div>
              {(item.state === 'uploading' || item.state === 'processing') && (
                <Progress className="mt-1" value={item.state === 'processing' ? 100 : percent} />
              )}
              {item.message && item.state === 'failed' && (
                <p className="mt-1 text-xs text-red-600">{item.message}</p>
              )}
            </li>
          );
        })}
      </ul>
    </Card>
  );
}
