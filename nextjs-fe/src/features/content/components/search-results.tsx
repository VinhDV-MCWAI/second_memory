'use client';

import Link from 'next/link';
import { useTranslations } from 'next-intl';
import { Folder } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useSearch } from '@/features/content/hooks/use-content-queries';
import { FileThumb } from '@/features/media/components/file-thumb';
import { filesHref, formatBytes, parentOf } from '@/features/media/lib/media-paths';
import { ADMIN_ROUTES, SEARCH_QUERY_MIN } from '@/shared/config';

/**
 * Results of the admin search, best match first. Files and folders (SEARCH-01) open the Files
 * screen on their folder.
 */
export function SearchResults({ q }: { q: string }) {
  const t = useTranslations('search');
  const tCommon = useTranslations('common');
  const { data, isLoading } = useSearch(q);

  if (q.trim().length < SEARCH_QUERY_MIN) {
    return <p className="text-muted-foreground">{t('hint', { min: SEARCH_QUERY_MIN })}</p>;
  }
  if (isLoading || !data) {
    return <p className="text-muted-foreground">{tCommon('loading')}</p>;
  }
  if (data.files.length === 0) {
    return <p className="text-muted-foreground">{t('empty', { q })}</p>;
  }

  return (
    <div className="space-y-4">
      {data.match === 'fuzzy' && <p className="text-sm text-amber-600">{t('fuzzy')}</p>}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>{t('files')}</CardTitle>
          <Link href={ADMIN_ROUTES.FILES} className="text-sm underline">
            {t('openFiles')}
          </Link>
        </CardHeader>
        <CardContent>
          <ul className="space-y-3">
            {data.files.map((item) => (
              <li key={item.id}>
                <Link
                  href={filesHref(item.is_file ? parentOf(item.path) : item.path)}
                  className="flex items-center gap-2 hover:underline"
                >
                  {item.is_file ? (
                    <FileThumb item={item} />
                  ) : (
                    <Folder className="h-4 w-4 shrink-0" aria-hidden />
                  )}
                  <span className="min-w-0">
                    <span className="block truncate font-medium">{item.name}</span>
                    <span className="block truncate text-sm text-muted-foreground">
                      {[parentOf(item.path), formatBytes(item.size)].filter(Boolean).join(' · ')}
                    </span>
                  </span>
                </Link>
              </li>
            ))}
          </ul>
        </CardContent>
      </Card>
    </div>
  );
}
