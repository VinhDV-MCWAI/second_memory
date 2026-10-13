'use client';

import { useState, type ComponentProps, type ReactNode } from 'react';
import { useTranslations } from 'next-intl';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { DIALOG_DEFAULTS } from '@/shared/config';
import { usePermissions } from '@/shared/hooks/use-permissions';

export type DialogSize = 'sm' | 'md' | 'lg' | 'xl' | 'full';

/** Desktop width of each size; on phones every dialog keeps a gutter on both sides. */
const SIZE_CLASSES: Record<DialogSize, string> = {
  sm: 'sm:max-w-md',
  md: 'sm:max-w-lg',
  lg: 'sm:max-w-2xl',
  xl: 'sm:max-w-4xl',
  full: 'sm:max-w-[95vw]',
};

/** One footer button. Only `key` and `label` are required; the rest falls back to the defaults. */
export interface DialogAction {
  key: string;
  label: string;
  /** Runs on click; every button stays locked while a returned promise is pending. */
  onClick?: () => void | Promise<void>;
  /** Id of a `<form>` this button submits instead of calling `onClick`. */
  form?: string;
  variant?: ComponentProps<typeof Button>['variant'];
  size?: ComponentProps<typeof Button>['size'];
  icon?: ReactNode;
  disabled?: boolean;
  /** Permission name (e.g. `role.delete`); the button is hidden without it. */
  permission?: string;
}

export interface AppDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: ReactNode;
  description?: ReactNode;
  /** The body: scrolls when taller than the dialog, header and footer stay fixed. */
  children: ReactNode;
  size?: DialogSize;
  /** Footer buttons after Cancel, left to right in this order. */
  actions?: DialogAction[];
  /** At most this many actions are shown (Cancel not counted). */
  maxActions?: number;
  showCancel?: boolean;
  /** Replaces the whole footer; `null` hides it. */
  footer?: ReactNode | null;
  /** Unsaved changes: closing asks to discard them first. */
  dirty?: boolean;
  /** Saving: buttons are locked and the dialog cannot be closed. */
  busy?: boolean;
}

/** The shared dialog: header (title, description, X), scrolling body, footer of declared buttons. */
export function AppDialog({
  open,
  onOpenChange,
  title,
  description,
  children,
  size = DIALOG_DEFAULTS.SIZE,
  actions = [],
  maxActions = DIALOG_DEFAULTS.MAX_ACTIONS,
  showCancel = true,
  footer,
  dirty = false,
  busy = false,
}: AppDialogProps) {
  const tCommon = useTranslations('common');
  const tDialog = useTranslations('dialog');
  const { can } = usePermissions();
  const [running, setRunning] = useState(false);
  const [confirmDiscard, setConfirmDiscard] = useState(false);
  const locked = busy || running;

  if (actions.length > maxActions && process.env.NODE_ENV !== 'production') {
    console.error(
      `AppDialog: ${actions.length} actions declared, only the first ${maxActions} are shown.`,
    );
  }
  const visibleActions = actions
    .slice(0, maxActions)
    .filter((action) => !action.permission || can(action.permission));

  // Cancel, X, Escape and a click outside all come through here
  const requestClose = () => {
    if (locked) return;
    if (dirty) {
      setConfirmDiscard(true);
    } else {
      onOpenChange(false);
    }
  };

  const run = async (onClick: NonNullable<DialogAction['onClick']>) => {
    setRunning(true);
    try {
      await onClick();
    } finally {
      setRunning(false);
    }
  };

  const defaultFooter = (showCancel || visibleActions.length > 0) && (
    <DialogFooter>
      {showCancel && (
        <Button type="button" variant="outline" onClick={requestClose} disabled={locked}>
          {tCommon('cancel')}
        </Button>
      )}
      {visibleActions.map(({ key, label, onClick, form, variant, size, icon, disabled }) => (
        <Button
          key={key}
          type={form ? 'submit' : 'button'}
          form={form}
          variant={variant ?? DIALOG_DEFAULTS.ACTION_VARIANT}
          size={size ?? DIALOG_DEFAULTS.ACTION_SIZE}
          disabled={locked || disabled}
          onClick={!form && onClick ? () => run(onClick) : undefined}
        >
          {icon}
          {label}
        </Button>
      ))}
    </DialogFooter>
  );
  const footerContent = footer === undefined ? defaultFooter : footer;

  return (
    <>
      <Dialog open={open} onOpenChange={(next) => (next ? onOpenChange(true) : requestClose())}>
        <DialogContent
          className={`flex max-h-[90vh] flex-col gap-0 overflow-hidden p-0 ${SIZE_CLASSES[size]}`}
        >
          <div className="shrink-0 border-b px-6 pt-6 pr-12 pb-4">
            <DialogHeader>
              <DialogTitle>{title}</DialogTitle>
              {description && <DialogDescription>{description}</DialogDescription>}
            </DialogHeader>
          </div>

          <div className="min-h-0 flex-1 overflow-y-auto px-6 py-4">{children}</div>

          {footerContent && (
            <div className="shrink-0 border-t bg-muted/20 px-6 py-4">{footerContent}</div>
          )}
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={confirmDiscard}
        onOpenChange={setConfirmDiscard}
        title={tDialog('discardTitle')}
        description={tDialog('discardDescription')}
        confirmText={tDialog('discard')}
        cancelText={tDialog('keepEditing')}
        variant="destructive"
        onConfirm={() => {
          setConfirmDiscard(false);
          onOpenChange(false);
        }}
      />
    </>
  );
}
