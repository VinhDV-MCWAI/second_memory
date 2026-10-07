'use client';

import Image from 'next/image';
import { useTranslations } from 'next-intl';
import { X, File, AlertCircle, Image as ImageIcon, Loader2, CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/shared/utils';
import type { UploadedFileData } from '@/features/media/types/file-manager.types';
import { formatFileSize } from '@/features/media/components/file-manager/utils';

interface UploadFileRowProps {
  uploadedFile: UploadedFileData;
  onRemove: () => void;
}

export function UploadFileRow({ uploadedFile, onRemove }: UploadFileRowProps) {
  const t = useTranslations('fileManager.dialogs');
  const { file, uploading, uploaded, error, preview, progress } = uploadedFile;

  return (
    <div
      className={cn(
        'flex items-start gap-3 rounded-md border p-3 text-sm',
        error ? 'border-destructive bg-destructive/5' : 'border-border',
      )}
    >
      {/* Preview or Icon */}
      <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center overflow-hidden rounded bg-muted">
        {uploading ? (
          <Loader2 className="h-5 w-5 animate-spin text-primary" />
        ) : preview ? (
          <div className="relative h-full w-full">
            <Image src={preview} alt={file.name} fill className="object-cover" />
          </div>
        ) : uploaded ? (
          <ImageIcon className="h-5 w-5 text-muted-foreground" />
        ) : (
          <File className="h-5 w-5 text-muted-foreground" />
        )}
      </div>

      {/* File Info */}
      <div className="min-w-0 flex-1">
        <div className="flex items-start justify-between gap-2">
          <div className="min-w-0 flex-1">
            <p className="truncate font-medium">{file.name}</p>
            <p className="text-xs text-muted-foreground">{formatFileSize(file.size)}</p>
          </div>

          {/* Status Icon */}
          <div className="flex-shrink-0">
            {uploading && <Loader2 className="h-4 w-4 animate-spin text-primary" />}
            {uploaded && !error && <CheckCircle2 className="h-4 w-4 text-green-600" />}
            {error && <AlertCircle className="h-4 w-4 text-destructive" />}
            {!uploading && (!uploaded || !error) && (
              <Button variant="ghost" size="icon" className="-mr-2 h-6 w-6" onClick={onRemove}>
                <X className="h-4 w-4" />
              </Button>
            )}
          </div>
        </div>

        {/* Error Message */}
        {error && <p className="mt-1 text-xs text-destructive">{error}</p>}

        {/* Progress Bar */}
        {uploading && progress !== undefined && (
          <div className="mt-2">
            <div className="mb-1 flex justify-between text-xs">
              <span className="text-primary">{t('upload.uploadingToTemp')}</span>
              <span className="font-medium text-primary">{progress}%</span>
            </div>
            <Progress value={progress} className="h-1.5" />
          </div>
        )}

        {/* Uploading Status without Progress */}
        {uploading && progress === undefined && (
          <p className="mt-1 text-xs text-primary">{t('upload.uploadingToTemp')}</p>
        )}
      </div>
    </div>
  );
}
