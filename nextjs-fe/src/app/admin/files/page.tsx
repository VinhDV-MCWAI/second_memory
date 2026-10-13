'use client';

import { Suspense } from 'react';
import { useTranslations } from 'next-intl';
import { PageHeader } from '@/components/layout/page-header';
import { MediaBrowser } from '@/features/media/components/media-browser';
import { ADMIN_ROUTES } from '@/shared/config';

export default function FilesPage() {
  const t = useTranslations('media');
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
      {/* useSearchParams (the open folder) needs a Suspense boundary */}
      <Suspense>
        <MediaBrowser />
      </Suspense>
    </>
  );
}
