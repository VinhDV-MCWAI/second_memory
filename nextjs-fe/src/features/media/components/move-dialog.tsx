'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { AppDialog } from '@/components/common/app-dialog';
import type { MediaItem } from '@/shared/types/models';
import { getApiErrorMessage } from '@/shared/utils/error-handler';
import { parentOf } from '../lib/media-paths';
import { FolderTreePicker } from './folder-tree-picker';

const FORM_ID = 'media-move-form';

interface MoveDialogProps {
  /** The file or folder to move; null closes the dialog. */
  item: MediaItem | null;
  onClose: () => void;
  onMove: (id: number, folder: string) => Promise<unknown>;
}

/** Move a file or folder: pick the target in the folder tree instead of typing its path. */
export function MoveDialog({ item, onClose, onMove }: MoveDialogProps) {
  // Keyed by the item, so each opening starts on the item's own folder
  return item && <MoveForm key={item.id} item={item} onClose={onClose} onMove={onMove} />;
}

function MoveForm({ item, onClose, onMove }: MoveDialogProps & { item: MediaItem }) {
  const t = useTranslations('media');
  const current = parentOf(item.path);
  const [target, setTarget] = useState(current);
  const [error, setError] = useState<string | undefined>();
  const [saving, setSaving] = useState(false);

  const submit = async (event: React.FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setError(undefined);
    try {
      await onMove(item.id, target);
      onClose();
    } catch (failure) {
      setError(getApiErrorMessage(failure) ?? t('moveFailed'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppDialog
      open
      onOpenChange={(open) => !open && onClose()}
      title={t('moveNamed', { name: item.name })}
      size="sm"
      dirty={target !== current}
      actions={[
        {
          key: 'submit',
          label: t('move'),
          form: FORM_ID,
          // Nothing to do while the target is the folder it is already in
          disabled: saving || target === current,
        },
      ]}
    >
      <form id={FORM_ID} onSubmit={submit} className="space-y-2">
        <p className="text-sm">
          {t('targetFolder')}: <span className="font-medium">{target}</span>
        </p>
        <FolderTreePicker
          value={target}
          onChange={setTarget}
          disabledPath={item.is_file ? undefined : item.path}
        />
        {error && <p className="text-sm text-destructive">{error}</p>}
      </form>
    </AppDialog>
  );
}
