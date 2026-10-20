'use client';

import { useTranslations } from 'next-intl';
import { PageHeader } from '@/components/layout/page-header';
import { Card } from '@/components/ui/card';
import { useDashboardSummary } from '@/features/content/hooks/use-content-queries';
import { BackgroundTaskTestCard } from '@/features/system/components/background-task-test-card';
import { useApiData } from '@/shared/hooks/use-api-data';
import { API_ENDPOINTS } from '@/shared/api';
import { ADMIN_ROUTES, SORT_ORDER } from '@/shared/config';
import type { AuditLogEntry, DashboardSummary } from '@/shared/types/models';

/** Rows of the "recent changes" card. */
const RECENT_CHANGES = 5;

const STAT_LABELS: Record<keyof DashboardSummary, string> = {
  categories: 'categories',
  articles: 'articles',
  public_articles: 'publicArticles',
};

export default function AdminDashboard() {
  const t = useTranslations('dashboard');
  const tCommon = useTranslations('common');
  const tHistory = useTranslations('history');
  const { data: summary } = useDashboardSummary();
  const { data: recent } = useApiData<AuditLogEntry>(API_ENDPOINTS.AUDIT.LOG, {
    per_page: RECENT_CHANGES,
    sort_by: 'id',
    sort_order: SORT_ORDER.DESC,
  });

  return (
    <>
      <PageHeader
        title={tCommon('dashboard')}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: tCommon('dashboard'), isActive: true },
        ]}
      />

      <div className="mt-8 grid gap-4 sm:grid-cols-3">
        {(Object.keys(STAT_LABELS) as (keyof DashboardSummary)[]).map((key) => (
          <Card key={key} className="p-6">
            <p className="text-sm font-medium text-muted-foreground">{t(STAT_LABELS[key])}</p>
            <p className="mt-2 text-3xl font-bold">{summary ? summary[key] : '–'}</p>
          </Card>
        ))}
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-3">
        <Card className="p-6">
          <h3 className="text-lg font-semibold">{t('recentChanges')}</h3>
          {recent.length === 0 ? (
            <p className="mt-4 text-sm text-muted-foreground">{t('noChanges')}</p>
          ) : (
            <ul className="mt-4 space-y-3">
              {recent.map((entry) => (
                <li key={entry.id} className="border-b pb-3 text-sm last:border-0">
                  <p className="font-medium">
                    {tHistory(`events.${entry.event}`)} · {entry.auditable_type} #
                    {entry.auditable_id}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {entry.actor_user_name ?? '—'} · {entry.created_at}
                  </p>
                </li>
              ))}
            </ul>
          )}
        </Card>
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-3">
        <BackgroundTaskTestCard />
      </div>
    </>
  );
}
