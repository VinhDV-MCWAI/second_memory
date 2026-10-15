'use client';

import { Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { PageHeader } from '@/components/layout/page-header';
import { SearchResults } from '@/features/ledger/components/search-results';
import { ADMIN_ROUTES, SEARCH_PARAM } from '@/shared/config';

function SearchPageContent() {
  const q = useSearchParams().get(SEARCH_PARAM) ?? '';
  return <SearchResults q={q} />;
}

export default function SearchPage() {
  const t = useTranslations('ledger.search');
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
      <div className="mt-6">
        {/* useSearchParams needs a Suspense boundary in the App Router */}
        <Suspense>
          <SearchPageContent />
        </Suspense>
      </div>
    </>
  );
}
