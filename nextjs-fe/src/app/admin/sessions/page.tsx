'use client';

import { useTranslations } from 'next-intl';
import { PageHeader } from '@/components/layout/page-header';
import { SessionList } from '@/features/master/components/session-list';
import { ADMIN_ROUTES } from '@/shared/config';

export default function SessionsPage() {
  const t = useTranslations('sessions');
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
      <SessionList />
    </>
  );
}
