'use client';

import { useTranslations } from 'next-intl';
import { PageHeader } from '@/components/layout/page-header';
import { AuditLogBrowser } from '@/features/history/components/audit-log-browser';
import { ADMIN_ROUTES } from '@/shared/config';

export default function AuditLogPage() {
  const t = useTranslations('auditLog');
  const tCommon = useTranslations('common');

  return (
    <>
      <PageHeader
        title={t('title')}
        description={t('description')}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: t('title'), isActive: true },
        ]}
      />
      <AuditLogBrowser />
    </>
  );
}
