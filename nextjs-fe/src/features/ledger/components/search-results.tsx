'use client';

import Link from 'next/link';
import { useTranslations } from 'next-intl';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useLedgerSearch } from '@/features/ledger/hooks/use-ledger-queries';
import { ADMIN_ROUTES, SEARCH_QUERY_MIN } from '@/shared/config';
import type { SearchResult } from '@/shared/types/models';

type Group = 'skills' | 'goals' | 'evidence';

const GROUP_ROUTES: Record<Group, string> = {
  skills: ADMIN_ROUTES.SKILLS,
  goals: ADMIN_ROUTES.GOALS,
  evidence: ADMIN_ROUTES.EVIDENCE,
};

/** Results of the admin search grouped by type, best match first (REQ-002 US-5). */
export function SearchResults({ q }: { q: string }) {
  const t = useTranslations('ledger.search');
  const tCommon = useTranslations('common');
  const { data, isLoading } = useLedgerSearch(q);

  if (q.trim().length < SEARCH_QUERY_MIN) {
    return <p className="text-muted-foreground">{t('hint', { min: SEARCH_QUERY_MIN })}</p>;
  }
  if (isLoading || !data) {
    return <p className="text-muted-foreground">{tCommon('loading')}</p>;
  }

  const groups = (Object.keys(GROUP_ROUTES) as Group[]).filter((group) => data[group].length > 0);
  if (groups.length === 0) {
    return <p className="text-muted-foreground">{t('empty', { q })}</p>;
  }

  return (
    <div className="space-y-4">
      {data.match === 'fuzzy' && <p className="text-sm text-amber-600">{t('fuzzy')}</p>}
      {groups.map((group) => (
        <Card key={group}>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>{t(group)}</CardTitle>
            <Link href={GROUP_ROUTES[group]} className="text-sm underline">
              {t('openList')}
            </Link>
          </CardHeader>
          <CardContent>
            <ul className="space-y-3">
              {data[group].map((item: SearchResult[Group][number]) => (
                <li key={item.id}>
                  <p className="font-medium">{item.title}</p>
                  {item.snippet && <p className="text-sm text-muted-foreground">{item.snippet}</p>}
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
