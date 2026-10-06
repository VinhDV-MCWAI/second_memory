'use client';

import {
  X,
  Copy,
  Download,
  Trash2,
  ChevronLeft,
  ChevronRight,
  FileText,
  Music,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { formatFileSize } from './utils';
import { format } from 'date-fns';
import toast from 'react-hot-toast';
import { useEffect } from 'react';
import { useTranslations } from 'next-intl';
import type { PreviewModalProps } from '@/shared/types/file-manager.types';
import {
  DATE_FORMATS,
  KEYBOARD_KEYS,
  KEYBOARD_EVENT,
  MIME_TYPE_PREFIX,
} from '@/shared/config/constant';

export const PreviewModal = ({
  file,
  onClose,
  onDelete,
  onNext,
  onPrev,
  hasNext,
  hasPrev,
}: PreviewModalProps) => {
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (!file) return;
      if (e.key === KEYBOARD_KEYS.ESCAPE) onClose();
      if (e.key === KEYBOARD_KEYS.ARROW_RIGHT && hasNext) onNext?.();
      if (e.key === KEYBOARD_KEYS.ARROW_LEFT && hasPrev) onPrev?.();
    };

    window.addEventListener(KEYBOARD_EVENT.KEYDOWN, handleKeyDown);
    return () => window.removeEventListener(KEYBOARD_EVENT.KEYDOWN, handleKeyDown);
  }, [file, onClose, hasNext, hasPrev, onNext, onPrev]);

  const t = useTranslations('fileManager');

  if (!file) return null;

  const handleCopyLink = () => {
    navigator.clipboard.writeText(file.url);
    toast.success(t('linkCopied'));
  };

  const handleDelete = () => {
    if (confirm(t('confirmDelete'))) {
      onDelete?.(file);
      onClose();
    }
  };

  const isImage = file.mime_type.startsWith(MIME_TYPE_PREFIX.IMAGE);
  const isVideo = file.mime_type.startsWith(MIME_TYPE_PREFIX.VIDEO);
  const isAudio = file.mime_type.startsWith('audio/');
  const isPdf = file.mime_type === 'application/pdf' || file.url.includes('.pdf');

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm">
      <div className="relative flex h-full max-h-[90vh] w-full max-w-6xl flex-col gap-4 md:flex-row">
        {/* Main Content Area */}
        <div className="relative flex min-h-[300px] flex-1 items-center justify-center overflow-hidden rounded-lg bg-black/50">
          {/* Navigation Buttons */}
          {hasPrev && (
            <Button
              onClick={(e) => {
                e.stopPropagation();
                onPrev?.();
              }}
              variant="ghost"
              size="icon"
              className="absolute left-4 z-20 h-12 w-12 rounded-full text-white hover:bg-white/20"
            >
              <ChevronLeft className="h-8 w-8" />
            </Button>
          )}

          {hasNext && (
            <Button
              onClick={(e) => {
                e.stopPropagation();
                onNext?.();
              }}
              variant="ghost"
              size="icon"
              className="absolute right-4 z-20 h-12 w-12 rounded-full text-white hover:bg-white/20"
            >
              <ChevronRight className="h-8 w-8" />
            </Button>
          )}

          {/* Close button */}
          <Button
            onClick={onClose}
            variant="ghost"
            size="sm"
            className="absolute top-4 right-4 z-20 text-white hover:bg-white/20"
          >
            <X className="h-6 w-6" />
          </Button>

          {/* Preview content */}
          <div className="flex h-full w-full items-center justify-center p-4">
            {isImage && (
              // eslint-disable-next-line @next/next/no-img-element
              <img
                src={file.url}
                alt={file.name}
                className="max-h-full max-w-full object-contain"
              />
            )}

            {isVideo && (
              <video src={file.url} className="max-h-full max-w-full" controls autoPlay />
            )}

            {isAudio && (
              <div className="flex flex-col items-center justify-center text-white">
                <Music className="mb-4 h-24 w-24" />
                <audio src={file.url} controls className="w-full max-w-md" />
              </div>
            )}

            {isPdf && (
              <iframe src={`${file.url}#toolbar=0`} className="h-full w-full rounded-md bg-white" />
            )}

            {!isImage && !isVideo && !isAudio && !isPdf && (
              <div className="flex flex-col items-center justify-center text-white">
                <FileText className="mb-4 h-24 w-24" />
                <p className="text-xl font-medium">{file.name}</p>
                <p className="mt-2 text-white/70">{t('previewNotAvailable')}</p>
              </div>
            )}
          </div>
        </div>

        {/* Sidebar Info */}
        <Card className="h-fit w-full flex-shrink-0 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:w-80">
          <div className="space-y-6 p-6">
            <div>
              <h3 className="mb-1 text-lg leading-none font-semibold tracking-tight break-words">
                {file.name}
              </h3>
              <p className="text-sm text-muted-foreground">{formatFileSize(file.size)}</p>
            </div>

            <div className="space-y-4 text-sm">
              <div className="grid grid-cols-3 gap-2">
                <span className="text-muted-foreground">{t('type')}:</span>
                <span className="col-span-2 truncate font-medium" title={file.mime_type}>
                  {file.mime_type}
                </span>
              </div>
              <div className="grid grid-cols-3 gap-2">
                <span className="text-muted-foreground">{t('created')}:</span>
                <span className="col-span-2 font-medium">
                  {format(new Date(file.created_at), DATE_FORMATS.LONG)}
                </span>
              </div>
              <div className="grid grid-cols-3 gap-2">
                <span className="text-muted-foreground">{t('folder')}:</span>
                <span className="col-span-2 truncate font-medium" title={file.folder_path}>
                  {file.folder_path}
                </span>
              </div>
            </div>

            <div className="flex flex-col gap-2 border-t pt-4">
              <Button onClick={handleCopyLink} variant="outline" className="w-full justify-start">
                <Copy className="mr-2 h-4 w-4" />
                {t('copyLink')}
              </Button>
              <Button asChild variant="outline" className="w-full justify-start">
                <a href={file.url} download>
                  <Download className="mr-2 h-4 w-4" />
                  {t('download')}
                </a>
              </Button>
              <Button onClick={handleDelete} variant="destructive" className="w-full justify-start">
                <Trash2 className="mr-2 h-4 w-4" />
                {t('deleteFile')}
              </Button>
            </div>
          </div>
        </Card>
      </div>
    </div>
  );
};
