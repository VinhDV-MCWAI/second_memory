'use client';

import Link from 'next/link';
import { useTranslations } from 'next-intl';
import { Paperclip } from 'lucide-react';
import { ADMIN_ROUTES, EVIDENCE_FILE_PARAMS } from '@/shared/config';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { MediaItem } from '@/shared/types/models';

/**
 * Where a row of the file screen is attached (MEDIA-09): a file lists its evidence in the tooltip
 * and links to them (LEDGER-03), a folder counts the attachments of the files inside.
 */
export function UsedBy({ item }: { item: MediaItem }) {
  const t = useTranslations('media.usedBy');
  const { can } = usePermissions();
  const count = item.attached_count ?? 0;
  if (count === 0) return null;

  const titles = (item.used_by ?? []).map((evidence) => evidence.title);
  const label = item.is_file
    ? t('file', { count, titles: titles.join(', ') })
    : t('folder', { count });
  const content = (
    <>
      <Paperclip className="h-3 w-3" aria-hidden />
      {count}
    </>
  );

  if (item.is_file && can('evidence.list')) {
    const query = new URLSearchParams({
      [EVIDENCE_FILE_PARAMS.ID]: String(item.id),
      [EVIDENCE_FILE_PARAMS.NAME]: item.name,
    });
    return (
      <Link
        href={`${ADMIN_ROUTES.EVIDENCE}?${query}`}
        className="flex items-center gap-1 text-xs text-blue-600 hover:underline dark:text-blue-400"
        title={label}
        aria-label={t('open', { name: item.name })}
      >
        {content}
      </Link>
    );
  }

  return (
    <span
      className="flex items-center gap-1 text-xs text-muted-foreground"
      title={label}
      aria-label={label}
    >
      {content}
    </span>
  );
}
