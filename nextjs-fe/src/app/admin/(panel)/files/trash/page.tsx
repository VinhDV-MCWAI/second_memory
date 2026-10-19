'use client';

import { useTranslations } from 'next-intl';
import { PageHeader } from '@/components/layout/page-header';
import { TrashBrowser } from '@/features/media/components/trash-browser';
import { TRASH_DAYS } from '@/features/media/hooks/use-trash';
import { ADMIN_ROUTES } from '@/shared/config';

export default function FilesTrashPage() {
  const t = useTranslations('media');
  const tCommon = useTranslations('common');

  return (
    <>
      <PageHeader
        title={t('trash.title')}
        description={t('trash.description', { days: TRASH_DAYS })}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: t('title'), href: ADMIN_ROUTES.FILES },
          { label: t('trash.title'), isActive: true },
        ]}
      />
      <TrashBrowser />
    </>
  );
}
