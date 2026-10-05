'use client';

import { useTranslations } from 'next-intl';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { AlertTriangle } from 'lucide-react';
import type { ConfirmCancelDialogProps } from '@/shared/types/role-wizard.types';

export function ConfirmCancelDialog({
  open,
  onOpenChange,
  onConfirm,
}: ConfirmCancelDialogProps) {
  const tCommon = useTranslations('common');
  const tWizard = useTranslations('roleWizard');

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <div className="flex items-center gap-2">
            <AlertTriangle className="h-5 w-5 text-yellow-600" />
            <AlertDialogTitle>{tWizard('confirmCancel')}</AlertDialogTitle>
          </div>
          <AlertDialogDescription>
            {tWizard('cancelConfirmMessage')}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <div className="flex justify-end gap-3">
          <AlertDialogCancel>{tCommon('continue')}</AlertDialogCancel>
          <AlertDialogAction
            onClick={onConfirm}
            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            {tCommon('cancel')}
          </AlertDialogAction>
        </div>
      </AlertDialogContent>
    </AlertDialog>
  );
}
