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
import { Upload, X, File, AlertCircle, Image as ImageIcon, Loader2, CheckCircle2 } from 'lucide-react';
import Image from 'next/image';
import { useTranslations } from 'next-intl';
import { UPLOAD_CONFIG } from '@/shared/config/constant';
import { cn } from "@/shared/utils";
import type { UploadDialogProps, ExtendedFile, UploadedFileData } from '@/shared/types/file-manager.types';
import { formatFileSize } from '../utils';
import { mediaFileService } from '@/shared/services/modules/media-file.service';
import { MultipartUploader } from '@/shared/services/multipart-uploader';

export const UploadDialog = ({
  open,
  onOpenChange,
  onUpload,
  currentPath,
  isLoading: isExternalLoading,
}: UploadDialogProps & { isLoading?: boolean }) => {
  const t = useTranslations('fileManager.dialogs');
  const [isDragging, setIsDragging] = useState(false);
  const [uploadedFiles, setUploadedFiles] = useState<UploadedFileData[]>([]);
  const [committing, setCommitting] = useState(false);
  const [commitProgress, setCommitProgress] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const isAnyFileUploading = uploadedFiles.some(f => f.uploading);
  const allFilesUploaded = uploadedFiles.length > 0 && uploadedFiles.every(f => f.uploaded || f.error);
  const hasValidFiles = uploadedFiles.some(f => f.uploaded);
  const hasAnyErrors = uploadedFiles.some(f => f.error);

  // Reset state when dialog closes
  useEffect(() => {
    if (!open) {
      setUploadedFiles([]);
      setError(null);
      setCommitProgress(0);
      setCommitting(false);
    }
  }, [open]);

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
  }, []);

  /* eslint-disable @typescript-eslint/no-unused-vars */
  const validateFile = (_file: File): string | null => {
    return null;
  };

  const generatePreview = async (file: File): Promise<string | undefined> => {
    if (!file.type.startsWith('image/')) {
      return undefined;
    }

    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onloadend = () => {
        resolve(reader.result as string);
      };
      reader.onerror = () => {
        resolve(undefined);
      };
      reader.readAsDataURL(file);
    });
  };

  const autoUploadToTemp = useCallback(async (file: File) => {
    const fileId = `${file.name}-${file.size}-${file.lastModified}`;
    
    // Check if already uploading or uploaded
    if (uploadedFiles.some(uf => `${uf.file.name}-${uf.file.size}-${uf.file.lastModified}` === fileId)) {
      return;
    }

    // Validate file
    const validationError = validateFile(file);
    if (validationError) {
      setUploadedFiles(prev => [...prev, {
        file,
        key: '',
        uploading: false,
        uploaded: false,
        error: validationError,
        metadata: {
          original_name: file.name,
          extension: file.name.split('.').pop() || '',
          mime_type: file.type,
          size: file.size,
        }
      }]);
      return;
    }

    // Add file to list with uploading state
    setUploadedFiles(prev => [...prev, {
      file,
      key: '',
      uploading: true,
      uploaded: false,
      metadata: {
        original_name: file.name,
        extension: file.name.split('.').pop() || '',
        mime_type: file.type,
        size: file.size,
      }
    }]);

    try {
      // Generate preview for images
      const preview = await generatePreview(file);

      if (file.size > UPLOAD_CONFIG.HEAVY_FILE_THRESHOLD_BYTES) {
        // Heavy File Upload Flow
        const uploader = new MultipartUploader(file, (progress) => {
             // Update progress
            setUploadedFiles(prev => prev.map(uf => {
              const id = `${uf.file.name}-${uf.file.size}-${uf.file.lastModified}`;
              if (id === fileId) {
                return { ...uf, progress: progress.percentage };
              }
              return uf;
            }));
        }, undefined, currentPath);

        const result = await uploader.start();
        
        // Mark as uploaded and isHeavyUploaded
        setUploadedFiles(prev => prev.map(uf => {
            const id = `${uf.file.name}-${uf.file.size}-${uf.file.lastModified}`;
            if (id === fileId) {
              // Attach flags to file object for useFileManager
              const extendedFile = uf.file as ExtendedFile;
              extendedFile.isHeavyUploaded = true;
              extendedFile.tempKey = result.key; // Use real key from backend
              extendedFile.tempMetadata = {
                original_name: result.original_name,
                extension: result.extension,
                mime_type: result.mime_type,
                size: result.size,
              };

              return {
                ...uf,
                key: result.key, // Use real key from backend
                preview,
                uploading: false,
                uploaded: true,
                metadata: {
                  original_name: result.original_name,
                  extension: result.extension,
                  mime_type: result.mime_type,
                  size: result.size,
                }
              };
            }
            return uf;
        }));

      } else {
        // Normal Upload Flow
        // Upload to temp bucket via presigned URL
        const metadata = await mediaFileService.uploadToMinio({ 
          file,
          onProgress: (percentage) => {
            // Update progress
            setUploadedFiles(prev => prev.map(uf => {
              const id = `${uf.file.name}-${uf.file.size}-${uf.file.lastModified}`;
              if (id === fileId) {
                return { ...uf, progress: percentage };
              }
              return uf;
            }));
          }
        });

        if (!metadata) {
            throw new Error(t('upload.failedToUploadToTemp'));
        }

        // Update file with success state
        setUploadedFiles(prev => prev.map(uf => {
            const id = `${uf.file.name}-${uf.file.size}-${uf.file.lastModified}`;
            if (id === fileId) {
              // Attach metadata to file object for useFileManager
              const extendedFile = uf.file as ExtendedFile;
              extendedFile.tempKey = metadata.key;
              extendedFile.tempMetadata = {
                original_name: metadata.original_name,
                extension: metadata.extension,
                mime_type: metadata.mime_type,
                size: metadata.size,
              };

              return {
                ...uf,
                key: metadata.key,
                preview,
                uploading: false,
                uploaded: true,
                metadata: {
                  original_name: metadata.original_name,
                  extension: metadata.extension,
                  mime_type: metadata.mime_type,
                  size: metadata.size,
                }
              };
            }
            return uf;
        }));
      }
    } catch (err) {
      console.error(t('upload.autoUploadError'), err);
      const errorMessage = err instanceof Error ? err.message : t('upload.uploadFailed');
      
      // Update file with error state
      setUploadedFiles(prev => prev.map(uf => {
        const id = `${uf.file.name}-${uf.file.size}-${uf.file.lastModified}`;
        if (id === fileId) {
          return {
            ...uf,
            uploading: false,
            uploaded: false,
            error: errorMessage,
          };
        }
        return uf;
      }));
    }
  }, [uploadedFiles, currentPath, t]);

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    if (e.dataTransfer.files?.length) {
      const newFiles = Array.from(e.dataTransfer.files);
      newFiles.forEach(file => autoUploadToTemp(file));
    }
  }, [autoUploadToTemp]);

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files?.length) {
      const newFiles = Array.from(e.target.files);
      newFiles.forEach(file => autoUploadToTemp(file));
    }
    // Reset input so same files can be selected again if needed
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const removeFile = (index: number) => {
    setUploadedFiles(prev => prev.filter((_, i) => i !== index));
  };

  const handleCommit = async () => {
    if (!hasValidFiles) return;

    setCommitting(true);
    setCommitProgress(0);
    setError(null);

    // Simulate progress
    const interval = setInterval(() => {
      setCommitProgress(prev => {
        if (prev >= UPLOAD_CONFIG.MAX_PROGRESS) return prev;
        return prev + UPLOAD_CONFIG.PROGRESS_INCREMENT;
      });
    }, UPLOAD_CONFIG.PROGRESS_INTERVAL_MS);

    try {
      // Get only successfully uploaded files
      const validFiles = uploadedFiles.filter(uf => uf.uploaded && !uf.error);
      
      // Create File objects with metadata for the onUpload callback
      // The hook's uploadFiles will call mediaFileService.upload with the temp keys
      const filesToCommit = validFiles.map(uf => {
        // Attach metadata to the File object for the service to use
        const fileWithMetadata = uf.file as File & { 
          tempKey?: string;
          tempMetadata?: typeof uf.metadata;
        };
        fileWithMetadata.tempKey = uf.key;
        fileWithMetadata.tempMetadata = uf.metadata;
        return fileWithMetadata;
      });

      await onUpload(filesToCommit);
      
      setCommitProgress(100);
      clearInterval(interval);
      
      // Only close dialog if successful
      setTimeout(() => {
        setUploadedFiles([]);
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
    <Dialog open={open} onOpenChange={(val) => !(committing || isAnyFileUploading) && onOpenChange(val)}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{t('upload.uploadFiles')}</DialogTitle>
          <DialogDescription>
            {t('upload.uploadTo', { path: currentPath })}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          {/* Drop Zone */}
          <div
            className={cn(
              'relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/25 px-6 py-10 text-center transition-colors hover:bg-muted/50',
              isDragging && 'border-primary bg-primary/5',
              (committing || isAnyFileUploading) && 'pointer-events-none opacity-50'
            )}
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
            onClick={() => !(committing || isAnyFileUploading || isExternalLoading) && fileInputRef.current?.click()}
          >
            <input
              ref={fileInputRef}
              type="file"
              multiple
              className="hidden"
              onChange={handleFileSelect}
              disabled={committing || isAnyFileUploading || isExternalLoading}
            />
            <div className="flex flex-col items-center gap-2 cursor-pointer">
              <div className="rounded-full bg-primary/10 p-4">
                <Upload className="h-6 w-6 text-primary" />
              </div>
              <div className="text-sm">
                <span className="font-semibold text-primary">{t('upload.clickToSelect')}</span> {t('upload.orDragDrop')}
              </div>
              <p className="text-xs text-muted-foreground">
                {t('upload.supportedFiles')}
              </p>
            </div>
          </div>

          {/* File List */}
          {uploadedFiles.length > 0 && (
            <div className="max-h-[300px] overflow-y-auto space-y-2">
              {uploadedFiles.map((uploadedFile, index) => (
                <div
                  key={`${uploadedFile.file.name}-${index}`}
                  className={cn(
                    "flex items-start gap-3 rounded-md border p-3 text-sm",
                    uploadedFile.error ? "border-destructive bg-destructive/5" : "border-border"
                  )}
                >
                  {/* Preview or Icon */}
                  <div className="flex-shrink-0 w-12 h-12 rounded overflow-hidden bg-muted flex items-center justify-center">
                    {uploadedFile.uploading ? (
                      <Loader2 className="h-5 w-5 animate-spin text-primary" />
                    ) : uploadedFile.preview ? (
                      <div className="relative w-full h-full">
                        <Image 
                          src={uploadedFile.preview} 
                          alt={uploadedFile.file.name} 
                          fill
                          className="object-cover"
                        />
                      </div>
                    ) : uploadedFile.uploaded ? (
                      <ImageIcon className="h-5 w-5 text-muted-foreground" />
                    ) : (
                      <File className="h-5 w-5 text-muted-foreground" />
                    )}
                  </div>

                  {/* File Info */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex-1 min-w-0">
                        <p className="truncate font-medium">{uploadedFile.file.name}</p>
                        <p className="text-xs text-muted-foreground">
                          {formatFileSize(uploadedFile.file.size)}
                        </p>
                      </div>
                      
                      {/* Status Icon */}
                      <div className="flex-shrink-0">
                        {uploadedFile.uploading && (
                          <Loader2 className="h-4 w-4 animate-spin text-primary" />
                        )}
                        {uploadedFile.uploaded && !uploadedFile.error && (
                          <CheckCircle2 className="h-4 w-4 text-green-600" />
                        )}
                        {uploadedFile.error && (
                          <AlertCircle className="h-4 w-4 text-destructive" />
                        )}
                        {!uploadedFile.uploading && !uploadedFile.uploaded && (
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-6 w-6 -mr-2"
                            onClick={() => removeFile(index)}
                          >
                            <X className="h-4 w-4" />
                          </Button>
                        )}
                        {!uploadedFile.uploading && uploadedFile.uploaded && !uploadedFile.error && (
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-6 w-6 -mr-2"
                            onClick={() => removeFile(index)}
                          >
                            <X className="h-4 w-4" />
                          </Button>
                        )}
                      </div>
                    </div>
                    
                    {/* Error Message */}
                    {uploadedFile.error && (
                      <p className="text-xs text-destructive mt-1">{uploadedFile.error}</p>
                    )}
                    
                    {/* Progress Bar */}
                    {uploadedFile.uploading && uploadedFile.progress !== undefined && (
                      <div className="mt-2">
                        <div className="flex justify-between text-xs mb-1">
                          <span className="text-primary">{t('upload.uploadingToTemp')}</span>
                          <span className="text-primary font-medium">{uploadedFile.progress}%</span>
                        </div>
                        <Progress value={uploadedFile.progress} className="h-1.5" />
                      </div>
                    )}
                    
                    {/* Uploading Status without Progress */}
                    {uploadedFile.uploading && uploadedFile.progress === undefined && (
                      <p className="text-xs text-primary mt-1">{t('upload.uploadingToTemp')}</p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* Global Error Message */}
          {error && (
            <div className="flex items-start gap-2 rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm">
              <AlertCircle className="h-4 w-4 flex-shrink-0 text-destructive mt-0.5" />
              <div className="flex-1">
                <p className="font-medium text-destructive">{t('upload.uploadFailed')}</p>
                <p className="text-destructive/90 mt-1">{error}</p>
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
              <AlertCircle className="h-4 w-4 flex-shrink-0 text-destructive mt-0.5" />
              <div className="flex-1">
                <p className="font-medium text-destructive">{t('upload.filesWithErrors')}</p>
                <p className="text-destructive/90 mt-1">{t('upload.removeFailedFiles')}</p>
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
              disabled={!hasValidFiles || committing || isAnyFileUploading || !allFilesUploaded || isExternalLoading || hasAnyErrors}
            >
              {committing || isExternalLoading ? t('upload.uploading') : t('upload.uploadButton')}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};
