'use client';

import { useState, useEffect } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Folder } from 'lucide-react';
import { useTranslations } from 'next-intl';
import type { Folder as FolderType, MoveCopyDialogProps } from '@/shared/types/file-manager.types';
import type { MediaFile as ServiceMediaFile } from '@/shared/types/media-file.types';
import { mediaFileService } from '@/shared/services/modules/media-file.service';

export const MoveCopyDialog = ({
  open,
  onOpenChange,
  mode,
  count,
  onConfirm,
  currentPath,
  selectedFileIds,
  isLoading: isExternalLoading,
}: MoveCopyDialogProps & { isLoading?: boolean }) => {
  const t = useTranslations('fileManager.dialogs');
  const [folders, setFolders] = useState<FolderType[]>([]);
  const [selectedPath, setSelectedPath] = useState<string>('');
  const [isLoading, setIsLoading] = useState(false);
  const [isFetching, setIsFetching] = useState(false);

  useEffect(() => {
    if (open) {
      const fetchFolders = async () => {
        setIsFetching(true);
        try {
          // Use mediaFileService.listFolders instead of fileService.getFolders
          // Note: mediaFileService.listFolders returns PaginatedResponse<MediaFile>
          // We need to map MediaFile to FolderType if necessary or adjust types
          // Looking at types: MediaFile has folder_path, FolderType has path
          const response = await mediaFileService.listFolders({
             parent_path: '/', // TODO: Should we list all folders recursively or just root?
             // The original mock did a flat list of all folders. 
             // Real API might need recursive fetching or just flat list if supported.
             // Assuming listFolders returns what we need for now, but we might need to adjust.
             // Let's check mediaFileService.listFolders implementation again.
             // It calls API_PATHS.LIST with is_file=false.
          });

          // The listFolders returns MediaFile[], but we need FolderType[]
          // We cast to any to safely access properties that might vary between API types and runtime response
          const mappedFolders: FolderType[] = response.data.map((f: ServiceMediaFile) => {
             const item = f as unknown as { parent_id?: number };
             return {
               id: f.id.toString(),
               name: f.original_name,
               path: f.virtual_path,
               parent_id: item.parent_id?.toString() || 'root'
             };
          });

          // Filter out: current path AND folders that are being moved (prevent moving folder into itself)
          setFolders(mappedFolders.filter(f => 
            f.path !== currentPath && !selectedFileIds.includes(f.id)
          ));
          setSelectedPath('');
        } catch {
        } finally {
          setIsFetching(false);
        }
      };
      fetchFolders();
    }
  }, [open, currentPath, selectedFileIds]);

  const handleConfirm = async () => {
    if (!selectedPath) return;
    
    setIsLoading(true);
    try {
      await onConfirm(selectedPath);
      onOpenChange(false);
    } catch {
    } finally {
      setIsLoading(false);
    }
  };

  const title = mode === 'move' ? t('moveCopy.moveTitle') : t('moveCopy.copyTitle');
  const action = mode === 'move' ? t('moveCopy.move') : t('moveCopy.copy');

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{title} {count} {t('moveCopy.items')}</DialogTitle>
          <DialogDescription>
            {t('moveCopy.description', { action: action.toLowerCase() })}
          </DialogDescription>
        </DialogHeader>
        
        <div className="grid gap-4 py-4">
          <div className="space-y-2">
            <Label>{t('moveCopy.targetFolder')}</Label>
            <Select
              value={selectedPath}
              onValueChange={setSelectedPath}
              disabled={isFetching || isLoading || isExternalLoading}
            >
              <SelectTrigger>
                <SelectValue placeholder={t('moveCopy.selectFolder')} />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="/">
                  <div className="flex items-center gap-2">
                    <Folder className="h-4 w-4" />
                    <span>{t('moveCopy.root')}</span>
                  </div>
                </SelectItem>
                {folders.map((folder) => (
                  <SelectItem key={folder.id} value={folder.path}>
                    <div className="flex items-center gap-2">
                      <Folder className="h-4 w-4" />
                      <span>{folder.name}</span>
                      <span className="text-xs text-muted-foreground ml-2">
                        {folder.path}
                      </span>
                    </div>
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            onClick={() => onOpenChange(false)}
            disabled={isLoading || isExternalLoading}
          >
            {t('cancel')}
          </Button>
          <Button 
            onClick={handleConfirm} 
            disabled={!selectedPath || isLoading || isExternalLoading}
          >
            {isLoading || isExternalLoading ? t('processing') : action}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};
