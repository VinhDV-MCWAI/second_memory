'use client';

import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import type { MediaFile } from '@/shared/types/file-manager.types';
import { formatFileSize, getFileIcon } from './utils';
import { format } from 'date-fns';
import { FileContextMenu } from './context-menu';
import { Folder } from 'lucide-react';
import { useTranslations } from 'next-intl';
import type { FileGridProps } from '@/shared/types/file-manager.types';
import { DATE_FORMATS, UI_CONSTANTS, MIME_TYPE_PREFIX, FILE_TYPE } from '@/shared/config/constant';

export const FileGrid = ({
  files,
  selectedFiles,
  onSelect,
  onFileClick,
  onNavigate,
  isLoading = false,
  onPreview,
  onRename,
  onMove,
  onCopy,
  onDelete,
  onDownload,
}: FileGridProps) => {
  const t = useTranslations('fileManager');
  
  if (isLoading) {
    return (
      <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        {Array.from({ length: UI_CONSTANTS.LOADING_SKELETON_COUNT }).map((_, i) => (
          <Card key={i} className="h-40 animate-pulse bg-muted" />
        ))}
      </div>
    );
  }

  if (files.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-12">
        <p className="text-lg text-muted-foreground">{t('noFiles')}</p>
      </div>
    );
  }

  const handleDoubleClick = (file: MediaFile) => {
    if (file.type === FILE_TYPE.FOLDER) {
      onNavigate(file.folder_path === '/' ? `/${file.name}` : `${file.folder_path}/${file.name}`);
    } else {
      onPreview(file);
    }
  };

  return (
    <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 pb-20">
      {files.map((file) => {
        const isSelected = selectedFiles.includes(file.id);
        const Icon = getFileIcon(file.mime_type);

        return (
          <FileContextMenu
            key={file.id}
            file={file}
            onPreview={onPreview}
            onRename={onRename}
            onMove={onMove}
            onCopy={onCopy}
            onDelete={onDelete}
            onDownload={onDownload}
          >
            <Card
              className={`group relative overflow-hidden transition-all hover:shadow-lg cursor-pointer ${
                isSelected ? 'ring-2 ring-primary shadow-md' : 'hover:shadow-primary/20'
              }`}
              onClick={() => onFileClick(file)}
              onDoubleClick={() => handleDoubleClick(file)}
            >
              {/* Checkbox */}
              <div className={`absolute right-2 top-2 z-10 flex items-center gap-2 transition-opacity ${isSelected ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'}`}>
                <Checkbox
                  checked={isSelected}
                  onCheckedChange={(checked: boolean | 'indeterminate') => {
                    onSelect(file.id, checked === true);
                  }}
                  onClick={(e: React.MouseEvent) => e.stopPropagation()}
                />
              </div>

              {/* Preview/Icon Area */}
              <div className="relative h-32 w-full overflow-hidden bg-muted">
                {file.type === FILE_TYPE.FOLDER ? (
                  <div className="flex h-full items-center justify-center bg-blue-50 dark:bg-blue-900/20">
                    <Folder className="h-16 w-16 text-blue-500" fill="currentColor" />
                  </div>
                ) : file.mime_type?.startsWith(MIME_TYPE_PREFIX.IMAGE) ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={file.url}
                    alt={file.name}
                    className="h-full w-full object-cover"
                    loading="lazy"
                  />
                ) : (
                  <div className="flex h-full items-center justify-center">
                    <Icon className="h-12 w-12 text-muted-foreground" />
                  </div>
                )}
              </div>

              {/* File Info */}
              <div className="space-y-1 p-3">
                <p className="truncate text-sm font-medium text-foreground" title={file.name}>
                  {file.name}
                </p>
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                  <span>{formatFileSize(file.size)}</span>
                  <span>{format(new Date(file.created_at), DATE_FORMATS.SHORT)}</span>
                </div>
              </div>
            </Card>
          </FileContextMenu>
        );
      })}
    </div>
  );
};
