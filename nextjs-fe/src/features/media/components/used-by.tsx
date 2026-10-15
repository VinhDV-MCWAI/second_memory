'use client';

import { useTranslations } from 'next-intl';
import { Paperclip } from 'lucide-react';
import type { MediaItem } from '@/shared/types/models';

/**
 * Where a row of the file screen is attached (MEDIA-09): a file lists its evidence in the tooltip,
 * a folder counts the attachments of the files inside.
 */
export function UsedBy({ item }: { item: MediaItem }) {
  const t = useTranslations('media.usedBy');
  const count = item.attached_count ?? 0;
  if (count === 0) return null;

  const titles = (item.used_by ?? []).map((evidence) => evidence.title);
  const label = item.is_file
    ? t('file', { count, titles: titles.join(', ') })
    : t('folder', { count });

  return (
    <span
      className="flex items-center gap-1 text-xs text-muted-foreground"
      title={label}
      aria-label={label}
    >
      <Paperclip className="h-3 w-3" aria-hidden />
      {count}
    </span>
  );
}
