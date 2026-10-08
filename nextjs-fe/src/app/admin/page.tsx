'use client';

import { useTranslations } from 'next-intl';
import { PageHeader } from '@/components/layout/page-header';
import { Card } from '@/components/ui/card';
import { useDashboardSummary } from '@/features/ledger/hooks/use-ledger-queries';
import { useApiData } from '@/shared/hooks/use-api-data';
import { API_ENDPOINTS } from '@/shared/api';
import { ADMIN_ROUTES, SORT_ORDER } from '@/shared/config';
import type { AuditLogEntry, DashboardSummary } from '@/shared/types/models';

/** Rows of the "recent changes" card. */
const RECENT_CHANGES = 5;

type CountKey = Exclude<keyof DashboardSummary, 'levels'>;

const STAT_LABELS: Record<CountKey, string> = {
  skills: 'skills',
  public_skills: 'publicSkills',
  evidence: 'evidence',
  public_evidence: 'publicEvidence',
  open_goals: 'openGoals',
};

export default function AdminDashboard() {
  const t = useTranslations('ledger.dashboard');
  const tCommon = useTranslations('common');
  const tHistory = useTranslations('history');
  const { data: summary } = useDashboardSummary();
  const { data: recent } = useApiData<AuditLogEntry>(API_ENDPOINTS.AUDIT.LOG, {
    per_page: RECENT_CHANGES,
    sort_by: 'id',
    sort_order: SORT_ORDER.DESC,
  });
  const mostPerLevel = Math.max(1, ...(summary?.levels.map((level) => level.count) ?? []));

  return (
    <>
      <PageHeader
        title={tCommon('dashboard')}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: tCommon('dashboard'), isActive: true },
        ]}
      />

      <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        {(Object.keys(STAT_LABELS) as CountKey[]).map((key) => (
          <Card key={key} className="p-6">
            <p className="text-sm font-medium text-muted-foreground">{t(STAT_LABELS[key])}</p>
            <p className="mt-2 text-3xl font-bold">{summary ? summary[key] : '–'}</p>
          </Card>
        ))}
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-3">
        <Card className="col-span-1 p-6 lg:col-span-2">
          <h3 className="text-lg font-semibold">{t('levels')}</h3>
          <ul className="mt-6 space-y-3">
            {summary?.levels.map((level) => (
              <li key={level.level} className="grid grid-cols-[10rem_1fr_2rem] items-center gap-3">
                <span className="text-sm">{level.level_label}</span>
                <div className="h-3 rounded bg-muted">
                  {/* Width is data, so it cannot be a Tailwind class */}
                  <div
                    className="h-3 rounded bg-primary"
                    style={{ width: `${(level.count / mostPerLevel) * 100}%` }}
                  />
                </div>
                <span className="text-right text-sm tabular-nums">{level.count}</span>
              </li>
            ))}
          </ul>
        </Card>

        <Card className="p-6">
          <h3 className="text-lg font-semibold">{t('recentActivity')}</h3>
          {recent.length === 0 ? (
            <p className="mt-4 text-sm text-muted-foreground">{t('noActivity')}</p>
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
    </>
  );
}
