'use client';

import type { ReactNode } from 'react';
import Image from 'next/image';
import { useTranslations } from 'next-intl';
import { Badge } from '@/components/ui/badge';

/** Highlighted when `active`, muted otherwise. */
export function StatusBadge({ active, children }: { active: boolean; children: ReactNode }) {
  return <Badge variant={active ? 'default' : 'secondary'}>{children}</Badge>;
}

/** Thumbnail for list rows, with a placeholder when there is no image. */
export function ImageCell({ src, alt }: { src?: string | null; alt: string }) {
  const tCommon = useTranslations('common');
  return (
    <div className="relative h-12 w-20 overflow-hidden rounded">
      {src ? (
        <Image src={src} alt={alt} fill className="object-cover" unoptimized />
      ) : (
        <div className="flex h-full w-full items-center justify-center bg-gray-200 text-xs text-gray-400 dark:bg-gray-700">
          {tCommon('noImage')}
        </div>
      )}
    </div>
  );
}
