'use client';

import type { ReactNode } from 'react';
import Link from 'next/link';
import { useTranslations } from 'next-intl';
import { Folder } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useSearch } from '@/features/content/hooks/use-content-queries';
import { FileThumb } from '@/features/media/components/file-thumb';
import { filesHref, formatBytes, parentOf } from '@/features/media/lib/media-paths';
import { ADMIN_ROUTES, SEARCH_QUERY_MIN, publicPaths } from '@/shared/config';

/**
 * Results of the admin search, best match first (ADR-0022): articles and categories (private ones
 * too; public ones link to their page on the public site), then files and folders (SEARCH-01),
 * which open the Files screen on their folder.
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
  if (data.articles.length + data.categories.length + data.files.length === 0) {
    return <p className="text-muted-foreground">{t('empty', { q })}</p>;
  }

  const privateBadge = <Badge variant="secondary">{t('private')}</Badge>;

  return (
    <div className="space-y-4">
      {data.match === 'fuzzy' && <p className="text-sm text-amber-600">{t('fuzzy')}</p>}

      {data.articles.length > 0 && (
        <Group title={t('articles')} href={ADMIN_ROUTES.ARTICLES} linkLabel={t('openArticles')}>
          {data.articles.map((article) => (
            <li key={article.id}>
              <span className="flex items-center gap-2">
                <PublicLink href={article.is_public ? publicPaths.article(article.slug) : null}>
                  {article.title}
                </PublicLink>
                {!article.is_public && privateBadge}
              </span>
              {article.snippet && (
                <span className="block text-sm text-muted-foreground">{article.snippet}</span>
              )}
            </li>
          ))}
        </Group>
      )}

      {data.categories.length > 0 && (
        <Group
          title={t('categories')}
          href={ADMIN_ROUTES.CONTENT_CATEGORIES}
          linkLabel={t('openCategories')}
        >
          {data.categories.map((category) => (
            <li key={category.id} className="flex items-center gap-2">
              <PublicLink href={category.is_public ? publicPaths.category(category.slug) : null}>
                {category.name}
              </PublicLink>
              {category.group && (
                <span className="text-sm text-muted-foreground">{category.group}</span>
              )}
              {!category.is_public && privateBadge}
            </li>
          ))}
        </Group>
      )}

      {data.files.length > 0 && (
        <Group title={t('files')} href={ADMIN_ROUTES.FILES} linkLabel={t('openFiles')}>
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
        </Group>
      )}
    </div>
  );
}

function Group({
  title,
  href,
  linkLabel,
  children,
}: {
  title: string;
  href: string;
  linkLabel: string;
  children: ReactNode;
}) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle>{title}</CardTitle>
        <Link href={href} className="text-sm underline">
          {linkLabel}
        </Link>
      </CardHeader>
      <CardContent>
        <ul className="space-y-3">{children}</ul>
      </CardContent>
    </Card>
  );
}

/** A title linking to the public site in a new tab; plain text when the page is not public. */
function PublicLink({ href, children }: { href: string | null; children: ReactNode }) {
  return href ? (
    <a href={href} target="_blank" rel="noreferrer" className="font-medium hover:underline">
      {children}
    </a>
  ) : (
    <span className="font-medium">{children}</span>
  );
}
