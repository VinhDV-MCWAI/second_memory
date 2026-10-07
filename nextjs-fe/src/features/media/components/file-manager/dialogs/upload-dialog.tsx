'use client';

import { useState, useRef, useCallback, useEffect } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Upload, X, AlertCircle } from 'lucide-react';
import { useTranslations } from 'next-intl';
import { UPLOAD_CONFIG } from '@/shared/config';
import { cn } from '@/shared/utils';
import type { UploadDialogProps, ExtendedFile } from '@/features/media/types/file-manager.types';
import { useTempUpload } from '@/features/media/hooks/use-temp-upload';
import { UploadFileRow } from './upload-file-row';

export const UploadDialog = ({
  open,
  onOpenChange,
  onUpload,
  currentPath,
  isLoading: isExternalLoading,
}: UploadDialogProps & { isLoading?: boolean }) => {
  const t = useTranslations('fileManager.dialogs');
  const [isDragging, setIsDragging] = useState(false);
  const { uploadedFiles, addFile, removeFile, reset } = useTempUpload(currentPath);
  const [committing, setCommitting] = useState(false);
  const [commitProgress, setCommitProgress] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const isAnyFileUploading = uploadedFiles.some((f) => f.uploading);
  const allFilesUploaded =
    uploadedFiles.length > 0 && uploadedFiles.every((f) => f.uploaded || f.error);
  const hasValidFiles = uploadedFiles.some((f) => f.uploaded);
  const hasAnyErrors = uploadedFiles.some((f) => f.error);

  // Reset state when dialog closes (also when the parent closes it)
  /* eslint-disable react-hooks/set-state-in-effect */
  useEffect(() => {
    if (!open) {
      reset();
      setError(null);
      setCommitProgress(0);
      setCommitting(false);
    }
  }, [open, reset]);
  /* eslint-enable react-hooks/set-state-in-effect */

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
  }, []);

  const handleDrop = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault();
      setIsDragging(false);
      if (e.dataTransfer.files?.length) {
        const newFiles = Array.from(e.dataTransfer.files);
        newFiles.forEach((file) => addFile(file));
      }
    },
    [addFile],
  );

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files?.length) {
      const newFiles = Array.from(e.target.files);
      newFiles.forEach((file) => addFile(file));
    }
    // Reset input so same files can be selected again if needed
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleCommit = async () => {
    if (!hasValidFiles) return;

    setCommitting(true);
    setCommitProgress(0);
    setError(null);

    // Simulate progress
    const interval = setInterval(() => {
      setCommitProgress((prev) => {
        if (prev >= UPLOAD_CONFIG.MAX_PROGRESS) return prev;
        return prev + UPLOAD_CONFIG.PROGRESS_INCREMENT;
      });
    }, UPLOAD_CONFIG.PROGRESS_INTERVAL_MS);

    try {
      // Get only successfully uploaded files
      const validFiles = uploadedFiles.filter((uf) => uf.uploaded && !uf.error);

      // Create File objects with metadata for the onUpload callback
      // The hook's uploadFiles will call mediaFileService.upload with the temp keys
      const filesToCommit = validFiles.map((uf) => {
        // Attach metadata to the File object for the service to use
        const fileWithMetadata = uf.file as ExtendedFile;
        fileWithMetadata.tempKey = uf.key;
        fileWithMetadata.tempMetadata = uf.metadata;
        return fileWithMetadata;
      });

      await onUpload(filesToCommit);

      setCommitProgress(100);
      clearInterval(interval);

      // Only close dialog if successful
      setTimeout(() => {
        reset();
        setCommitting(false);
        setCommitProgress(0);
        onOpenChange(false);
      }, UPLOAD_CONFIG.COMPLETE_DELAY_MS);
    } catch (err: unknown) {
      clearInterval(interval);
      setCommitting(false);
      setCommitProgress(0);

      // Extract error message
      let errorMessage = t('upload.uploadError');
      if (err instanceof Error) {
        errorMessage = err.message;
      }

      setError(errorMessage);
      console.error(t('upload.commitError'), err);
    }
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(val) => !(committing || isAnyFileUploading) && onOpenChange(val)}
    >
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{t('upload.uploadFiles')}</DialogTitle>
          <DialogDescription>{t('upload.uploadTo', { path: currentPath })}</DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          {/* Drop Zone */}
          <div
            className={cn(
              'relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/25 px-6 py-10 text-center transition-colors hover:bg-muted/50',
              isDragging && 'border-primary bg-primary/5',
              (committing || isAnyFileUploading) && 'pointer-events-none opacity-50',
            )}
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
            onClick={() =>
              !(committing || isAnyFileUploading || isExternalLoading) &&
              fileInputRef.current?.click()
            }
          >
            <input
              ref={fileInputRef}
              type="file"
              multiple
              className="hidden"
              onChange={handleFileSelect}
              disabled={committing || isAnyFileUploading || isExternalLoading}
            />
            <div className="flex cursor-pointer flex-col items-center gap-2">
              <div className="rounded-full bg-primary/10 p-4">
                <Upload className="h-6 w-6 text-primary" />
              </div>
              <div className="text-sm">
                <span className="font-semibold text-primary">{t('upload.clickToSelect')}</span>{' '}
                {t('upload.orDragDrop')}
              </div>
              <p className="text-xs text-muted-foreground">{t('upload.supportedFiles')}</p>
            </div>
          </div>

          {/* File List */}
          {uploadedFiles.length > 0 && (
            <div className="max-h-[300px] space-y-2 overflow-y-auto">
              {uploadedFiles.map((uploadedFile, index) => (
                <UploadFileRow
                  key={`${uploadedFile.file.name}-${index}`}
                  uploadedFile={uploadedFile}
                  onRemove={() => removeFile(index)}
                />
              ))}
            </div>
          )}

          {/* Global Error Message */}
          {error && (
            <div className="flex items-start gap-2 rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm">
              <AlertCircle className="mt-0.5 h-4 w-4 flex-shrink-0 text-destructive" />
              <div className="flex-1">
                <p className="font-medium text-destructive">{t('upload.uploadFailed')}</p>
                <p className="mt-1 text-destructive/90">{error}</p>
              </div>
              <Button
                variant="ghost"
                size="icon"
                className="h-6 w-6"
                onClick={() => setError(null)}
              >
                <X className="h-4 w-4" />
              </Button>
            </div>
          )}

          {/* Files with errors warning */}
          {hasAnyErrors && !error && (
            <div className="flex items-start gap-2 rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm">
              <AlertCircle className="mt-0.5 h-4 w-4 flex-shrink-0 text-destructive" />
              <div className="flex-1">
                <p className="font-medium text-destructive">{t('upload.filesWithErrors')}</p>
                <p className="mt-1 text-destructive/90">{t('upload.removeFailedFiles')}</p>
              </div>
            </div>
          )}

          {/* Commit Progress Bar */}
          {committing && (
            <div className="space-y-2">
              <div className="flex justify-between text-xs">
                <span>{t('upload.finalizing')}</span>
                <span>{commitProgress}%</span>
              </div>
              <Progress value={commitProgress} className="h-2" />
            </div>
          )}

          {/* Actions */}
          <div className="flex justify-end gap-2">
            <Button
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={committing || isAnyFileUploading || isExternalLoading}
            >
              {t('cancel')}
            </Button>
            <Button
              onClick={handleCommit}
              disabled={
                !hasValidFiles ||
                committing ||
                isAnyFileUploading ||
                !allFilesUploaded ||
                isExternalLoading ||
                hasAnyErrors
              }
            >
              {committing || isExternalLoading ? t('upload.uploading') : t('upload.uploadButton')}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};
