'use client';

import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { HistoryViewer } from '@/components/features/history/history-viewer';
import type { RoleMst } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { RoleWizardDialog } from './role-wizard-dialog';
import type { RoleFormProps } from './types';

export function RoleForm({ initialData, onSuccess, onCancel }: RoleFormProps) {
  const tCommon = useTranslations('common');
  const [wizardOpen, setWizardOpen] = useState(true);
  const isEdit = !!initialData;

  const handleWizardOpenChange = (open: boolean) => {
    if (!open) {
      onCancel();
    }
    setWizardOpen(open);
  };

  const handleWizardSuccess = () => {
    setWizardOpen(false);
    onSuccess();
  };

  // For edit mode, show tabs with history option
  if (isEdit) {
    return (
      <>
        <RoleWizardDialog
          open={wizardOpen}
          onOpenChange={handleWizardOpenChange}
          initialData={initialData}
          onSuccess={handleWizardSuccess}
        />
        
        {/* History tab is shown in the parent role list page */}
      </>
    );
  }

  // For create mode, just show the wizard
  return (
    <RoleWizardDialog
      open={wizardOpen}
      onOpenChange={handleWizardOpenChange}
      initialData={initialData}
      onSuccess={handleWizardSuccess}
    />
  );
}
