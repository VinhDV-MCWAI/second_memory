'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { ArrowDown, ArrowUp, Download, Eye, Paperclip, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { FilePickerDialog } from '@/features/media/components/file-picker-dialog';
import { FileThumb } from '@/features/media/components/file-thumb';
import { IconButton } from '@/features/media/components/icon-button';
import { PreviewDialog } from '@/features/media/components/preview-dialog';
import { useMediaActions } from '@/features/media/hooks/use-media';
import { formatFileSize } from '@/features/media/lib/media-paths';
import { usePermissions } from '@/shared/hooks/use-permissions';
import { getApiErrorMessage, notification } from '@/shared/utils';
import { ValidationRules } from '@/shared/validation/validation-rules';
import type { EvidenceFile, MediaItem } from '@/shared/types/models';

interface EvidenceFilesFieldProps {
  /** Attached file ids in display order (`media_ids`). */
  value: number[];
  onChange: (ids: number[]) => void;
  /** Files the evidence had when the form opened (names, sizes, availability). */
  initialFiles: EvidenceFile[];
  disabled?: boolean;
}

/**
 * Files of the file manager attached to an evidence (LEDGER-01): pick, reorder, remove, preview.
 * A file deleted in the file manager stays attached and is marked; restoring it brings it back.
 */
export function EvidenceFilesField({
  value,
  onChange,
  initialFiles,
  disabled,
}: EvidenceFilesFieldProps) {
  const t = useTranslations('ledger.files');
  const tMedia = useTranslations('media');
  const { can } = usePermissions();
  const actions = useMediaActions();
  const [known, setKnown] = useState<EvidenceFile[]>(initialFiles);
  const [picking, setPicking] = useState(false);
  const [previewing, setPreviewing] = useState<MediaItem | null>(null);

  const files = value
    .map((id) => known.find((file) => file.id === id))
    .filter((file): file is EvidenceFile => file !== undefined);
  const canOpen = can('media.download-url');
  // A file in a folder the admin may not use keeps its name but does not open (ADR-0017)
  const opens = (file: EvidenceFile) => file.available && !file.locked && canOpen;
  const room = ValidationRules.EVIDENCE_FILES_MAX - value.length;

  const add = (picked: MediaItem[]) => {
    setKnown((previous) => [
      ...previous.filter((file) => !picked.some((item) => item.id === file.id)),
      ...picked.map((item) => ({ ...item, available: true, locked: false })),
    ]);
    onChange([...value, ...picked.map((item) => item.id)]);
  };

  const move = (index: number, offset: number) => {
    const ids = [...value];
    [ids[index], ids[index + offset]] = [ids[index + offset], ids[index]];
    onChange(ids);
  };

  const download = async (file: MediaItem) => {
    try {
      await actions.download(file.id);
    } catch (error) {
      notification.error(getApiErrorMessage(error) ?? tMedia('downloadFailed'));
    }
  };

  return (
    <div className="space-y-2">
      {files.length === 0 ? (
        <p className="text-sm text-muted-foreground">{t('empty')}</p>
      ) : (
        <ul className="divide-y rounded-md border">
          {files.map((file, index) => (
            <li key={file.id} className="flex items-center gap-2 px-3 py-1.5 text-sm">
              <FileThumb item={file} />
              <span
                className={
                  file.available ? 'truncate' : 'truncate text-muted-foreground line-through'
                }
              >
                {file.name}
              </span>
              <span className="shrink-0 text-muted-foreground">{formatFileSize(file)}</span>
              {file.available && file.locked && (
                <Badge variant="secondary" title={t('lockedHint')}>
                  {t('locked')}
                </Badge>
              )}
              {!file.available && (
                <Badge variant="secondary" title={t('deletedHint')}>
                  {t('deleted')}
                </Badge>
              )}
              <span className="ml-auto flex shrink-0 items-center">
                {opens(file) && file.preview && (
                  <IconButton label={tMedia('preview.open')} onClick={() => setPreviewing(file)}>
                    <Eye className="h-4 w-4" />
                  </IconButton>
                )}
                {opens(file) && (
                  <IconButton label={tMedia('download')} onClick={() => download(file)}>
                    <Download className="h-4 w-4" />
                  </IconButton>
                )}
                {!disabled && (
                  <>
                    <IconButton
                      label={t('moveUp')}
                      disabled={index === 0}
                      onClick={() => move(index, -1)}
                    >
                      <ArrowUp className="h-4 w-4" />
                    </IconButton>
                    <IconButton
                      label={t('moveDown')}
                      disabled={index === files.length - 1}
                      onClick={() => move(index, 1)}
                    >
                      <ArrowDown className="h-4 w-4" />
                    </IconButton>
                    <IconButton
                      label={t('remove', { name: file.name })}
                      onClick={() => onChange(value.filter((id) => id !== file.id))}
                    >
                      <X className="h-4 w-4" />
                    </IconButton>
                  </>
                )}
              </span>
            </li>
          ))}
        </ul>
      )}

      {!disabled && can('media.list') && room > 0 && (
        <Button type="button" variant="outline" size="sm" onClick={() => setPicking(true)}>
          <Paperclip className="mr-2 h-4 w-4" />
          {t('attach')}
        </Button>
      )}

      {/* Mounted only while open: it lists a folder as soon as it renders */}
      {picking && (
        <FilePickerDialog
          open
          onOpenChange={setPicking}
          chosenIds={value}
          limit={room}
          onPick={add}
        />
      )}
      <PreviewDialog
        item={previewing}
        onClose={() => setPreviewing(null)}
        onDownload={canOpen ? download : undefined}
      />
    </div>
  );
}
