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
import { CheckCircle } from 'lucide-react';
import type { ConfirmSubmitDialogProps } from '@/shared/types/role-wizard.types';

export function ConfirmSubmitDialog({
  open,
  onOpenChange,
  onConfirm,
  isLoading = false,
}: ConfirmSubmitDialogProps) {
  const tCommon = useTranslations('common');
  const tWizard = useTranslations('roleWizard');

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <div className="flex items-center gap-2">
            <CheckCircle className="h-5 w-5 text-green-600" />
            <AlertDialogTitle>{tWizard('confirmSubmit')}</AlertDialogTitle>
          </div>
          <AlertDialogDescription>
            {tWizard('submitConfirmMessage')}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <div className="flex justify-end gap-3">
          <AlertDialogCancel disabled={isLoading}>
            {tCommon('back')}
          </AlertDialogCancel>
          <AlertDialogAction
            onClick={onConfirm}
            disabled={isLoading}
            className="bg-primary text-primary-foreground hover:bg-primary/90"
          >
            {isLoading ? tCommon('processing') : tWizard('confirm')}
          </AlertDialogAction>
        </div>
      </AlertDialogContent>
    </AlertDialog>
  );
}
