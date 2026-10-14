'use client';

import { useQuery } from '@tanstack/react-query';
import { useTranslations } from 'next-intl';
import { Download } from 'lucide-react';
import { AppDialog } from '@/components/common/app-dialog';
import { queryKeys } from '@/shared/api';
import type { MediaItem } from '@/shared/types/models';
import { usePreviewUrl } from '../hooks/use-media';
import { formatBytes } from '../lib/media-paths';

/** laravel-api App\Enums\PreviewKind. */
export const PREVIEW_KIND = {
  IMAGE: 'image',
  PDF: 'pdf',
  VIDEO: 'video',
  AUDIO: 'audio',
  TEXT: 'text',
} as const;

interface PreviewDialogProps {
  item: MediaItem | null;
  onClose: () => void;
  onDownload?: (item: MediaItem) => void;
}

/**
 * Shows a file without downloading it (MEDIA-06). The server only offers a preview for types a
 * browser renders without running code, and serves them with a type it chose itself.
 */
export function PreviewDialog({ item, onClose, onDownload }: PreviewDialogProps) {
  const t = useTranslations('media.preview');
  const url = usePreviewUrl(item?.preview ? item.id : null);

  return (
    <AppDialog
      open={item !== null}
      onOpenChange={(open) => !open && onClose()}
      title={item?.name ?? ''}
      description={item ? `${item.path} · ${formatBytes(item.size)}` : undefined}
      size="xl"
      actions={
        item && onDownload
          ? [
              {
                key: 'download',
                label: t('download'),
                icon: <Download className="mr-2 h-4 w-4" />,
                variant: 'outline',
                onClick: () => onDownload(item),
              },
            ]
          : []
      }
    >
      {url.isError && <p className="text-sm text-red-600">{t('failed')}</p>}
      {url.isPending && <p className="text-sm text-muted-foreground">{t('loading')}</p>}
      {item && url.data && <PreviewBody kind={item.preview} name={item.name} url={url.data} />}
    </AppDialog>
  );
}

function PreviewBody({ kind, name, url }: { kind: string | null; name: string; url: string }) {
  switch (kind) {
    case PREVIEW_KIND.IMAGE:
      // eslint-disable-next-line @next/next/no-img-element -- a short-lived presigned URL, not a static asset
      return <img src={url} alt={name} className="mx-auto max-h-[70vh] object-contain" />;
    case PREVIEW_KIND.VIDEO:
      return <video src={url} controls className="mx-auto max-h-[70vh] w-full" />;
    case PREVIEW_KIND.AUDIO:
      return <audio src={url} controls className="w-full" />;
    case PREVIEW_KIND.PDF:
      return <iframe src={url} title={name} className="h-[70vh] w-full rounded border" />;
    case PREVIEW_KIND.TEXT:
      return <TextPreview url={url} />;
    default:
      return null;
  }
}

/** Text is read and shown as text (a <pre>), so nothing in it can run. */
function TextPreview({ url }: { url: string }) {
  const t = useTranslations('media.preview');
  const text = useQuery({
    queryKey: queryKeys.query(url),
    queryFn: async () => {
      const response = await fetch(url);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      return response.text();
    },
  });

  if (text.isError) return <p className="text-sm text-red-600">{t('failed')}</p>;
  if (text.isPending) return <p className="text-sm text-muted-foreground">{t('loading')}</p>;
  return (
    <pre className="max-h-[70vh] overflow-auto rounded bg-muted p-3 text-xs break-words whitespace-pre-wrap">
      {text.data}
    </pre>
  );
}
