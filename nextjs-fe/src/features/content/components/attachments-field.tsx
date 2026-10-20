'use client';

import { useState } from 'react';
import {
  useFieldArray,
  type Control,
  type FieldErrors,
  type UseFormRegister,
} from 'react-hook-form';
import { useTranslations } from 'next-intl';
import { ArrowDown, ArrowUp, Link2, Paperclip, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FilePickerDialog } from '@/features/media/components/file-picker-dialog';
import { FileThumb } from '@/features/media/components/file-thumb';
import { IconButton } from '@/features/media/components/icon-button';
import { formatFileSize } from '@/features/media/lib/media-paths';
import { usePermissions } from '@/shared/hooks/use-permissions';
import { ValidationRules } from '@/shared/validation/validation-rules';
import type { ContentCategoryFormData } from '@/shared/validation/validation';
import type { AttachedFile, MediaItem } from '@/shared/types/models';

interface AttachmentsFieldProps {
  control: Control<ContentCategoryFormData>;
  register: UseFormRegister<ContentCategoryFormData>;
  errors: FieldErrors<ContentCategoryFormData>;
  /** Files the category had when the form opened, by media id (names, sizes, availability). */
  initialFiles: AttachedFile[];
}

/**
 * Evidence of a category (ADR-0022): links and files of the file manager, each with a title and a
 * note, in the order they are shown. A file deleted in Files stays attached and is marked.
 */
export function AttachmentsField({
  control,
  register,
  errors,
  initialFiles,
}: AttachmentsFieldProps) {
  const t = useTranslations('content');
  const { can } = usePermissions();
  const { fields, append, remove, swap } = useFieldArray({ control, name: 'attachments' });
  const [known, setKnown] = useState<AttachedFile[]>(initialFiles);
  const [picking, setPicking] = useState(false);
  const room = ValidationRules.ATTACHMENTS_MAX - fields.length;
  const fileOf = (mediaId: number | null) => known.find((file) => file.id === mediaId);

  const attach = (picked: MediaItem[]) => {
    setKnown((previous) => [
      ...previous,
      ...picked.map((item) => ({ ...item, available: true, locked: false })),
    ]);
    picked.forEach((item) => append({ title: item.name, url: '', media_id: item.id, note: '' }));
  };

  return (
    <div className="space-y-2">
      {fields.length === 0 ? (
        <p className="text-sm text-muted-foreground">{t('noAttachments')}</p>
      ) : (
        <ul className="space-y-2">
          {fields.map((field, index) => {
            const file = fileOf(field.media_id);
            const rowErrors = errors.attachments?.[index];
            return (
              <li key={field.id} className="space-y-2 rounded-md border p-3">
                <div className="flex items-center gap-2">
                  <Input
                    aria-label={t('attachmentTitle')}
                    placeholder={t('attachmentTitle')}
                    {...register(`attachments.${index}.title`)}
                    aria-invalid={!!rowErrors?.title}
                  />
                  <IconButton
                    label={t('moveUp')}
                    disabled={index === 0}
                    onClick={() => swap(index, index - 1)}
                  >
                    <ArrowUp className="h-4 w-4" />
                  </IconButton>
                  <IconButton
                    label={t('moveDown')}
                    disabled={index === fields.length - 1}
                    onClick={() => swap(index, index + 1)}
                  >
                    <ArrowDown className="h-4 w-4" />
                  </IconButton>
                  <IconButton
                    label={t('remove', { name: field.title })}
                    onClick={() => remove(index)}
                  >
                    <X className="h-4 w-4" />
                  </IconButton>
                </div>
                {rowErrors?.title && (
                  <p className="text-sm text-destructive">{rowErrors.title.message}</p>
                )}
                {file && (
                  <div className="flex items-center gap-2 text-sm">
                    <FileThumb item={file} />
                    <span
                      className={
                        file.available ? 'truncate' : 'truncate text-muted-foreground line-through'
                      }
                    >
                      {file.name}
                    </span>
                    <span className="shrink-0 text-muted-foreground">{formatFileSize(file)}</span>
                    {!file.available && <Badge variant="secondary">{t('fileDeleted')}</Badge>}
                    {file.available && file.locked && (
                      <Badge variant="secondary">{t('fileLocked')}</Badge>
                    )}
                  </div>
                )}
                <Input
                  aria-label={t('attachmentUrl')}
                  placeholder="https://…"
                  {...register(`attachments.${index}.url`)}
                  aria-invalid={!!rowErrors?.url}
                />
                {rowErrors?.url && (
                  <p className="text-sm text-destructive">{rowErrors.url.message}</p>
                )}
                <Input
                  aria-label={t('attachmentNote')}
                  placeholder={t('attachmentNote')}
                  {...register(`attachments.${index}.note`)}
                />
              </li>
            );
          })}
        </ul>
      )}

      {room > 0 && (
        <div className="flex gap-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => append({ title: '', url: '', media_id: null, note: '' })}
          >
            <Link2 className="mr-2 h-4 w-4" />
            {t('addLink')}
          </Button>
          {can('media.list') && (
            <Button type="button" variant="outline" size="sm" onClick={() => setPicking(true)}>
              <Paperclip className="mr-2 h-4 w-4" />
              {t('attachFiles')}
            </Button>
          )}
        </div>
      )}

      {/* Mounted only while open: it lists a folder as soon as it renders */}
      {picking && (
        <FilePickerDialog
          open
          onOpenChange={setPicking}
          chosenIds={fields
            .map((field) => field.media_id)
            .filter((id): id is number => id !== null)}
          limit={room}
          onPick={attach}
        />
      )}
    </div>
  );
}
