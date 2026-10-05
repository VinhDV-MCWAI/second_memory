'use client';

import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Trash2, Archive, CheckCircle, XCircle, MoreHorizontal } from 'lucide-react';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { useTranslations } from 'next-intl';
import type { BulkAction, BulkActionsProps } from '@/shared/types/data-table.types';
import { useGlobalLoading } from '@/shared/context/global-loading-context';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { UI_CONSTANTS } from '@/shared/config';

export type { BulkAction } from '@/shared/types/data-table.types';

export function BulkActions({
  selectedIds,
  onClearSelection,
  actions = [],
  isLoading = false,
}: BulkActionsProps) {
  const [confirmAction, setConfirmAction] = useState<BulkAction | null>(null);
  const { showGlobalLoading, hideGlobalLoading } = useGlobalLoading();
  const t = useTranslations('bulkActions');
  const tCommon = useTranslations('common');

  const { execute, isLoading: isExecuting } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const handleActionClick = (action: BulkAction) => {
    if (action.confirmMessage) {
      setConfirmAction(action);
    } else {
      executeAction(action);
    }
  };

  const executeAction = async (action: BulkAction) => {
    await execute(async () => {
      showGlobalLoading();
      try {
        await action.onClick(selectedIds);
        onClearSelection();
        setConfirmAction(null);
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      } catch (_error) {
      } finally {
        hideGlobalLoading();
      }
    });
  };

  if (selectedIds.length === 0) {
    return null;
  }

  const defaultActions: BulkAction[] = [
    {
      label: t('deleteSelected'),
      icon: <Trash2 className="h-4 w-4" />,
      variant: 'destructive',
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      onClick: async (_ids) => {
        // Placeholder action
      },
      confirmMessage: t('deleteConfirm', { count: selectedIds.length }),
      confirmTitle: t('deleteItems'),
    },
    {
      label: t('archiveSelected'),
      icon: <Archive className="h-4 w-4" />,
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      onClick: async (_ids) => {
        // Placeholder action
      },
    },
    {
      label: t('activateSelected'),
      icon: <CheckCircle className="h-4 w-4" />,
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      onClick: async (_ids) => {
        // Placeholder action
      },
    },
    {
      label: t('deactivateSelected'),
      icon: <XCircle className="h-4 w-4" />,
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      onClick: async (_ids) => {
        // Placeholder action
      },
    },
  ];

  const allActions = actions.length > 0 ? actions : defaultActions;

  return (
    <>
      <div className="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
        <span className="text-sm font-medium text-blue-900 dark:text-blue-100">
          {t('itemsSelected', { count: selectedIds.length })}
        </span>
        
        <div className="ml-auto flex items-center gap-2">
          {allActions.slice(0, 2).map((action, index) => (
            <Button
              key={index}
              size="sm"
              variant={action.variant || 'outline'}
              onClick={() => handleActionClick(action)}
              disabled={isLoading || isExecuting}
              className="gap-2"
            >
              {action.icon}
              {action.label}
            </Button>
          ))}
          
          {allActions.length > 2 && (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button
                  size="sm"
                  variant="outline"
                  disabled={isLoading || isExecuting}
                >
                  <MoreHorizontal className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                {allActions.slice(2).map((action, index) => (
                  <DropdownMenuItem
                    key={index}
                    onClick={() => handleActionClick(action)}
                    className="gap-2"
                  >
                    {action.icon}
                    {action.label}
                  </DropdownMenuItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>
          )}

          <DropdownMenuSeparator className="h-6" />
          
          <Button
            size="sm"
            variant="ghost"
            onClick={onClearSelection}
            disabled={isLoading || isExecuting}
          >
            {t('clear')}
          </Button>
        </div>
      </div>

      {/* Confirmation Dialog */}
      <AlertDialog open={!!confirmAction} onOpenChange={() => setConfirmAction(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {confirmAction?.confirmTitle || t('confirmAction')}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {confirmAction?.confirmMessage}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={isExecuting}>{tCommon('cancel')}</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => confirmAction && executeAction(confirmAction)}
              disabled={isExecuting}
            >
              {isExecuting ? t('processing') : t('continue')}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}
