'use client';

import { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from 'next-intl';
import type { RenameDialogProps } from '@/shared/types/file-manager.types';
import { isValidFileName } from '../utils';

export const RenameDialog = ({
  open,
  onOpenChange,
  file,
  onRename,
  isLoading = false,
}: RenameDialogProps) => {
  const t = useTranslations('fileManager.dialogs');
  const [newName, setNewName] = useState(file?.name || '');
  const [error, setError] = useState('');



  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file) return;

    if (!newName.trim()) {
      setError(t('rename.emptyNameError'));
      return;
    }

    if (!isValidFileName(newName)) {
      setError(t('rename.invalidNameError'));
      return;
    }

    if (newName === file.name) {
      onOpenChange(false);
      return;
    }

    try {
      await onRename(file, newName);
      onOpenChange(false);
    } catch {
      setError(t('rename.renameError'));
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{t('rename.title')}</DialogTitle>
          <DialogDescription>
            {t('rename.description')} <span className="font-medium">{file?.name}</span>
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-4 items-center gap-4">
              <Label htmlFor="name" className="text-right">
                {t('rename.newNameLabel')}
              </Label>
              <div className="col-span-3">
                <Input
                  id="name"
                  value={newName}
                  onChange={(e) => {
                    setNewName(e.target.value);
                    setError('');
                  }}
                  autoFocus
                />
                {error && <p className="mt-2 text-sm text-red-500">{error}</p>}
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isLoading}
            >
              {t('cancel')}
            </Button>
            <Button type="submit" disabled={isLoading || !newName.trim()}>
              {isLoading ? t('processing') : t('rename.saveButton')}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
