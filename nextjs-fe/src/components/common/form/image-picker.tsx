'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { Button } from '@/components/ui/button';
import { Image as ImageIcon, X } from 'lucide-react';
import { MediaSelectorModal } from '@/components/common/media-selector-modal';
import type { MediaFile } from '@/shared/types/media-file.types';
import type { ImagePickerProps } from '@/shared/types/ui.types';

export function ImagePicker({ value, onChange, error, label, required }: ImagePickerProps) {
  const tCommon = useTranslations('common');
  const [mediaSelectorOpen, setMediaSelectorOpen] = useState(false);

  // We only track the URL via value prop. 
  // If parent needs ID, it should handle it via onChange callback arguments.

  const handleMediaSelect = (media: MediaFile) => {
    if (media.url) {
      onChange(media.url, media.id);
    }
    setMediaSelectorOpen(false);
  };

  const handleRemoveImage = () => {
    onChange('');
  };

  return (
    <div className="space-y-2">
      {label && (
        <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
          {label} {required && <span className="text-red-500">*</span>}
        </label>
      )}
      
      <div 
        className="border-2 border-dashed rounded-lg p-4 flex flex-col items-center justify-center min-h-[200px] relative bg-muted/10 hover:bg-muted/20 transition-colors cursor-pointer"
        onClick={() => setMediaSelectorOpen(true)}
      >
        {value ? (
          <div className="relative w-full h-full min-h-[200px] flex items-center justify-center">
            <Image 
              src={value} 
              alt="Preview" 
              fill 
              className="object-contain" 
              unoptimized 
            />
            <div className="absolute top-2 right-2 flex gap-2">
              <Button 
                type="button" 
                variant="destructive" 
                size="icon" 
                className="h-8 w-8 rounded-full shadow-md"
                onClick={(e) => {
                  e.stopPropagation();
                  handleRemoveImage();
                }}
              >
                <X className="h-4 w-4" />
              </Button>
            </div>
            <div className="absolute bottom-2 right-2">
              <Button 
                type="button" 
                variant="secondary" 
                size="sm" 
                className="shadow-md"
                onClick={(e) => {
                  e.stopPropagation();
                  setMediaSelectorOpen(true);
                }}
              >
                {tCommon('changeImage')}
              </Button>
            </div>
          </div>
        ) : (
          <div className="flex flex-col items-center gap-4">
            <div className="p-4 bg-background rounded-full shadow-sm">
              <ImageIcon className="h-8 w-8 text-muted-foreground" />
            </div>
            <div className="text-center space-y-1">
              <p className="text-sm font-medium">{tCommon('noImageSelected')}</p>
              <p className="text-xs text-muted-foreground">{tCommon('clickToSelectImage')}</p>
            </div>
            <Button type="button" variant="outline" onClick={(e) => {
              e.stopPropagation();
              setMediaSelectorOpen(true);
            }}>
              {tCommon('selectImage')}
            </Button>
          </div>
        )}
      </div>
      
      {error && <p className="text-sm text-red-500">{error}</p>}
      
      <MediaSelectorModal 
        open={mediaSelectorOpen} 
        onClose={() => setMediaSelectorOpen(false)} 
        onSelect={handleMediaSelect}
        allowedMimeTypes={['image/']}
      />
    </div>
  );
}
