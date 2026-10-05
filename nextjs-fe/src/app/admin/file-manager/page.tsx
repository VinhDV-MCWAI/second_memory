'use client';

import { AdminLayout } from '@/components/layout/admin-layout';
import { PageHeader } from '@/components/layout/page-header';
import { FileManagerContent } from '@/components/common/file-manager/file-manager-content';
import { FileManagerProvider } from '@/components/common/file-manager/context';
import { useTranslations } from 'next-intl';
import { ADMIN_ROUTES } from '@/shared/config';

export default function FileManagerPage() {
  const tCommon = useTranslations('common');
  const tEntities = useTranslations('entities');
  const tManagement = useTranslations('management');

  return (
    <AdminLayout>
      {/* Page Header */}
      <PageHeader
        title={tManagement('title', { entity: tEntities('fileManager') })}
        description={tManagement('description', { entity: tEntities('fileManager').toLowerCase() })}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: tEntities('fileManager'), isActive: true }
        ]}
      />

      {/* File Manager Content */}
      <div className="h-[calc(100vh-16rem)] overflow-hidden">
        <FileManagerProvider>
          <FileManagerContent />
        </FileManagerProvider>
      </div>
    </AdminLayout>
  );
}
