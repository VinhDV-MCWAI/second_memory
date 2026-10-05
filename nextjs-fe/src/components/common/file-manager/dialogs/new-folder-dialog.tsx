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
import type { NewFolderDialogProps } from '@/shared/types/file-manager.types';
import { isValidFileName } from '../utils';

export const NewFolderDialog = ({
  open,
  onOpenChange,
  onCreateFolder,
  isLoading = false,
}: NewFolderDialogProps) => {
  const t = useTranslations('fileManager.dialogs');
  const [folderName, setFolderName] = useState('');
  const [error, setError] = useState('');



  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!folderName.trim()) {
      setError(t('newFolder.emptyNameError'));
      return;
    }

    if (!isValidFileName(folderName)) {
      setError(t('newFolder.invalidNameError'));
      return;
    }

    try {
      await onCreateFolder(folderName);
      onOpenChange(false);
    } catch {
      setError(t('newFolder.createError'));
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{t('newFolder.title')}</DialogTitle>
          <DialogDescription>
            {t('newFolder.description')}
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-4 items-center gap-4">
              <Label htmlFor="name" className="text-right">
                {t('newFolder.nameLabel')}
              </Label>
              <div className="col-span-3">
                <Input
                  id="name"
                  value={folderName}
                  onChange={(e) => {
                    setFolderName(e.target.value);
                    setError('');
                  }}
                  placeholder={t('newFolder.placeholder')}
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
            <Button type="submit" disabled={isLoading || !folderName.trim()}>
              {isLoading ? t('creating') : t('newFolder.createButton')}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
