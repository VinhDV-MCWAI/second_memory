'use client';

import { useState, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { X, Image as ImageIcon } from 'lucide-react';
import { cn } from '@/shared/utils';
import toast from 'react-hot-toast';
import { useTranslations } from 'next-intl';
import {
  UPLOAD_CONFIG,
  IMAGE_SHAPES,
  MIME_TYPE_PREFIX,
  type ImageShape,
} from '@/shared/config/constant';
import type { ImageUploadProps } from '@/shared/types/data-table.types';

export function ImageUpload({
  value,
  onChange,
  maxSize = UPLOAD_CONFIG.DEFAULT_IMAGE_MAX_SIZE_MB,
  className,
  label,
  shape = IMAGE_SHAPES.RECTANGLE,
  aspectRatio,
}: ImageUploadProps) {
  const t = useTranslations();
  const [preview, setPreview] = useState<string | null>(value || null);
  const [isDragging, setIsDragging] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Reset the preview when the controlled value changes (adjust state during render,
  // https://react.dev/learn/you-might-not-need-an-effect#adjusting-some-state-when-a-prop-changes).
  const [prevValue, setPrevValue] = useState(value);
  if (value !== prevValue) {
    setPrevValue(value);
    setPreview(value || null);
  }

  const handleFileChange = (file: File | null) => {
    if (!file) {
      setPreview(null);
      onChange(null, null);
      return;
    }

    // Validate file type
    if (!file.type.startsWith(MIME_TYPE_PREFIX.IMAGE)) {
      toast.error(t('media.invalidFileType', { accept: 'image/*' }));
      return;
    }

    // Validate file size
    if (file.size > maxSize * 1024 * 1024) {
      toast.error(t('media.fileSizeExceeded', { maxSize }));
      return;
    }

    // Create preview
    const reader = new FileReader();
    reader.onloadend = () => {
      const previewUrl = reader.result as string;
      setPreview(previewUrl);
      onChange(file, previewUrl);
    };
    reader.readAsDataURL(file);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);

    const file = e.dataTransfer.files[0];
    handleFileChange(file);
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => {
    setIsDragging(false);
  };

  const handleClick = () => {
    fileInputRef.current?.click();
  };

  const handleRemove = () => {
    setPreview(null);
    onChange(null, null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const shapeClasses: Record<ImageShape, string> = {
    [IMAGE_SHAPES.SQUARE]: 'w-48 h-48 rounded-lg',
    [IMAGE_SHAPES.RECTANGLE]: 'w-full h-48 rounded-lg',
    [IMAGE_SHAPES.CIRCLE]: 'w-40 h-40 rounded-full',
  };

  return (
    <div className={cn('space-y-2', className)}>
      <Label>{label || t('upload.image')}</Label>

      <div
        className={cn(
          'relative flex cursor-pointer items-center justify-center overflow-hidden border-2 border-dashed transition-colors',
          shapeClasses[shape],
          aspectRatio,
          isDragging ? 'border-primary bg-primary/10' : 'border-gray-300 hover:border-primary',
          preview && 'border-solid',
        )}
        onDrop={handleDrop}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onClick={handleClick}
      >
        <input
          ref={fileInputRef}
          type="file"
          accept="image/*"
          className="hidden"
          onChange={(e) => handleFileChange(e.target.files?.[0] || null)}
        />

        {preview ? (
          <>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src={preview}
              alt={t('media.preview')}
              className={cn(
                'h-full w-full object-cover',
                shape === IMAGE_SHAPES.CIRCLE ? 'rounded-full' : 'rounded-lg',
              )}
            />
            <Button
              type="button"
              variant="destructive"
              size="icon"
              className="absolute -top-2 -right-2 z-10 h-8 w-8 rounded-full"
              onClick={(e) => {
                e.stopPropagation();
                handleRemove();
              }}
            >
              <X className="h-4 w-4" />
            </Button>
          </>
        ) : (
          <div className="flex flex-col items-center gap-2 p-4 text-center">
            <ImageIcon className="h-8 w-8 text-gray-400" />
            <div className="text-sm text-gray-500">
              <span className="font-medium text-primary">{t('media.clickToUpload')}</span>
              <br />
              {t('media.orDragAndDrop')}
            </div>
            <p className="text-xs text-gray-400">{t('upload.imageFormats', { maxSize })}</p>
          </div>
        )}
      </div>
    </div>
  );
}
