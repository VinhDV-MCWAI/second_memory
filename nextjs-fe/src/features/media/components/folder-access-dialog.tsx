'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { AppDialog } from '@/components/common/app-dialog';
import { IdPicker } from '@/features/ledger/components/id-picker';
import { API_ENDPOINTS } from '@/shared/api';
import { getApiErrorMessage, notification } from '@/shared/utils';
import type { MediaItem } from '@/shared/types/models';

interface FolderAccessDialogProps {
  /** The folder being given to departments; null closes the dialog. */
  folder: MediaItem | null;
  onClose: () => void;
  onSave: (id: number, departmentIds: number[]) => Promise<unknown>;
}

/**
 * Gives a folder to departments (ADR-0017): with at least one ticked, only their members (and
 * owners) see and use it and everything inside; none ticked opens it to everyone with Files rights.
 */
export function FolderAccessDialog({ folder, onClose, onSave }: FolderAccessDialogProps) {
  return (
    <FolderAccessForm
      // A fresh selection each time another folder is opened
      key={folder?.id ?? 'closed'}
      folder={folder}
      onClose={onClose}
      onSave={onSave}
    />
  );
}

function FolderAccessForm({ folder, onClose, onSave }: FolderAccessDialogProps) {
  const t = useTranslations('media.access');
  const tForms = useTranslations('forms.placeholders');
  const initial = folder?.department_ids ?? [];
  const [selected, setSelected] = useState<number[]>(initial);
  const dirty = selected.length !== initial.length || selected.some((id) => !initial.includes(id));

  const save = async () => {
    if (!folder) return;
    try {
      await onSave(folder.id, selected);
      notification.success(selected.length > 0 ? t('locked') : t('opened'));
      onClose();
    } catch (error) {
      notification.error(getApiErrorMessage(error) ?? t('failed'));
    }
  };

  return (
    <AppDialog
      open={folder !== null}
      onOpenChange={(open) => !open && onClose()}
      title={t('title', { name: folder?.name ?? '' })}
      description={t('description')}
      size="md"
      dirty={dirty}
      actions={[{ key: 'save', label: t('save'), onClick: save, disabled: !dirty }]}
    >
      <IdPicker
        endpoint={API_ENDPOINTS.MASTER.DEPARTMENT}
        emptyMessage={tForms('noDepartments')}
        value={selected}
        onChange={setSelected}
      />
      <p className="mt-3 text-xs text-muted-foreground">
        {selected.length > 0 ? t('lockedHint') : t('openHint')}
      </p>
    </AppDialog>
  );
}
