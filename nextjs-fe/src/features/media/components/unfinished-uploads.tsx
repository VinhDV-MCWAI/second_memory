'use client';

import { useRef, useState } from 'react';
import { useTranslations } from 'next-intl';
import { Play, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Progress } from '@/components/ui/progress';
import type { UnfinishedUpload } from '@/shared/types/models';
import { formatBytes } from '../lib/media-paths';

interface UnfinishedUploadsProps {
  uploads: UnfinishedUpload[];
  onResume: (upload: UnfinishedUpload, file: File) => void;
  onDiscard: (upload: UnfinishedUpload) => Promise<void>;
}

/**
 * Large uploads from an earlier visit that stopped midway. The browser cannot reopen a file by
 * itself, so resuming asks for the same file again; only the missing parts are then sent.
 */
export function UnfinishedUploads({ uploads, onResume, onDiscard }: UnfinishedUploadsProps) {
  const t = useTranslations('media.unfinished');
  const tCommon = useTranslations('common');
  const fileInput = useRef<HTMLInputElement>(null);
  const [resuming, setResuming] = useState<UnfinishedUpload | null>(null);
  const [discarding, setDiscarding] = useState<UnfinishedUpload | null>(null);
  const [pending, setPending] = useState(false);

  if (uploads.length === 0) return null;

  const pick = (upload: UnfinishedUpload) => {
    setResuming(upload);
    fileInput.current?.click();
  };

  const discard = async () => {
    if (!discarding) return;
    setPending(true);
    try {
      await onDiscard(discarding);
      setDiscarding(null);
    } finally {
      setPending(false);
    }
  };

  return (
    <Card className="p-4">
      <h3 className="text-sm font-semibold">{t('title')}</h3>
      <p className="mb-3 text-xs text-muted-foreground">{t('hint')}</p>
      <input
        ref={fileInput}
        type="file"
        hidden
        data-testid="media-resume-input"
        onChange={(event) => {
          const file = event.target.files?.[0];
          if (file && resuming) onResume(resuming, file);
          setResuming(null);
          event.target.value = '';
        }}
      />
      <ul className="space-y-3">
        {uploads.map((upload) => {
          const percent =
            upload.size === 0 ? 100 : Math.floor((upload.uploaded_bytes / upload.size) * 100);
          return (
            <li key={upload.id} className="text-sm">
              <div className="flex items-center gap-2">
                <span className="min-w-0 flex-1 truncate font-medium" title={upload.name}>
                  {upload.name}
                  <span className="ml-2 text-xs font-normal text-muted-foreground">
                    {upload.folder}
                  </span>
                </span>
                <span className="shrink-0 text-xs text-muted-foreground">
                  {`${percent}% · ${formatBytes(upload.uploaded_bytes)} / ${formatBytes(upload.size)}`}
                </span>
                <Button size="sm" variant="outline" onClick={() => pick(upload)}>
                  <Play className="mr-1 h-3 w-3" />
                  {t('resume')}
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8"
                  aria-label={t('discard')}
                  title={t('discard')}
                  onClick={() => setDiscarding(upload)}
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
              <Progress className="mt-1" value={percent} />
            </li>
          );
        })}
      </ul>
      <ConfirmDialog
        open={discarding !== null}
        onOpenChange={(open) => !open && setDiscarding(null)}
        title={t('discardTitle', { name: discarding?.name ?? '' })}
        description={t('discardDescription')}
        onConfirm={discard}
        confirmText={tCommon('delete')}
        variant="destructive"
        isLoading={pending}
      />
    </Card>
  );
}
