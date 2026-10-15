'use client';

import { useRef, useState } from 'react';
import { useTranslations } from 'next-intl';
import { ChevronRight, File as FileIcon, Folder, Paperclip, Upload } from 'lucide-react';
import { AppDialog } from '@/components/common/app-dialog';
import { Pagination } from '@/components/common/data-table/pagination';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { SORT_ORDER } from '@/shared/config';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { MediaItem } from '@/shared/types/models';
import { MEDIA_PER_PAGE, useMediaActions, useMediaList } from '../hooks/use-media';
import { useUploads } from '../hooks/use-uploads';
import { crumbsOf, formatBytes, ROOT } from '../lib/media-paths';
import { UploadPanel } from './upload-panel';

/** laravel-api UploadStatus::COMPLETED: only finished uploads can be picked. */
const STATUS_COMPLETED = 2;

interface FilePickerDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  /** Files already chosen: shown ticked and not picked again. */
  chosenIds: number[];
  /** How many more files may be picked. */
  limit: number;
  onPick: (files: MediaItem[]) => void;
}

/**
 * Picks files of the file manager (LEDGER-01): browse folders, filter by name, upload into the open
 * folder when allowed, tick files. Folders cannot be picked.
 */
export function FilePickerDialog({
  open,
  onOpenChange,
  chosenIds,
  limit,
  onPick,
}: FilePickerDialogProps) {
  const t = useTranslations('media');
  const tPicker = useTranslations('media.picker');
  const { can } = usePermissions();
  const [folder, setFolder] = useState(ROOT);
  const [name, setName] = useState('');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(MEDIA_PER_PAGE);
  const [picked, setPicked] = useState<MediaItem[]>([]);
  const fileInput = useRef<HTMLInputElement>(null);

  const { data, loading, pagination } = useMediaList({
    folder,
    name,
    page,
    perPage,
    sortBy: 'original_name',
    sortOrder: SORT_ORDER.ASC,
  });
  const actions = useMediaActions();
  const uploads = useUploads(() => void actions.refresh());
  const canUpload = can('media.upload-url') && can('media.multipart-start');

  // Closing clears the ticks, in case the caller keeps the dialog mounted
  const changeOpen = (next: boolean) => {
    if (!next) setPicked([]);
    onOpenChange(next);
  };

  const openFolder = (path: string) => {
    setFolder(path);
    setPage(1);
  };

  const toggle = (item: MediaItem, checked: boolean) =>
    setPicked((previous) =>
      checked ? [...previous, item] : previous.filter((file) => file.id !== item.id),
    );

  const pick = () => {
    onPick(picked);
    changeOpen(false);
  };

  return (
    <AppDialog
      open={open}
      onOpenChange={changeOpen}
      title={tPicker('title')}
      description={tPicker('description', { count: limit })}
      size="lg"
      actions={[
        {
          key: 'pick',
          label: tPicker('attach', { count: picked.length }),
          icon: <Paperclip className="mr-2 h-4 w-4" />,
          disabled: picked.length === 0,
          onClick: pick,
        },
      ]}
    >
      <div className="space-y-3">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <nav aria-label={t('breadcrumb')} className="flex flex-wrap items-center gap-1 text-sm">
            <button
              type="button"
              className="font-medium hover:underline"
              onClick={() => openFolder(ROOT)}
            >
              {t('root')}
            </button>
            {crumbsOf(folder).map((crumb) => (
              <span key={crumb.path} className="flex items-center gap-1">
                <ChevronRight className="h-4 w-4 text-slate-400" aria-hidden />
                <button
                  type="button"
                  className="hover:underline"
                  onClick={() => openFolder(crumb.path)}
                >
                  {crumb.name}
                </button>
              </span>
            ))}
          </nav>
          <div className="flex items-center gap-2">
            <Input
              className="w-40"
              placeholder={t('filterPlaceholder')}
              aria-label={t('filterPlaceholder')}
              value={name}
              onChange={(event) => {
                setName(event.target.value);
                setPage(1);
              }}
            />
            {canUpload && (
              <>
                <Button type="button" variant="outline" onClick={() => fileInput.current?.click()}>
                  <Upload className="mr-2 h-4 w-4" />
                  {t('upload')}
                </Button>
                <input
                  ref={fileInput}
                  type="file"
                  multiple
                  hidden
                  data-testid="picker-file-input"
                  onChange={(event) => {
                    const files = event.target.files;
                    if (files && files.length > 0) {
                      uploads.add(
                        Array.from(files).map((file) => ({ file })),
                        folder,
                      );
                    }
                    event.target.value = '';
                  }}
                />
              </>
            )}
          </div>
        </div>

        {uploads.items.length > 0 && (
          <UploadPanel
            items={uploads.items}
            onCancel={uploads.cancel}
            onClearFinished={uploads.clearFinished}
          />
        )}

        {loading && data.length === 0 ? (
          <p className="text-sm text-muted-foreground">{tPicker('loading')}</p>
        ) : data.length === 0 ? (
          <p className="text-sm text-muted-foreground">{tPicker('empty')}</p>
        ) : (
          <ul className="divide-y rounded-md border">
            {data.map((item) => {
              if (!item.is_file) {
                return (
                  <li key={item.id}>
                    <button
                      type="button"
                      className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-medium text-blue-600 hover:bg-muted dark:text-blue-400"
                      onClick={() => openFolder(item.path)}
                    >
                      <Folder className="h-4 w-4 shrink-0" aria-hidden />
                      <span className="truncate">{item.name}</span>
                    </button>
                  </li>
                );
              }
              const chosen = chosenIds.includes(item.id);
              const checked = chosen || picked.some((file) => file.id === item.id);
              const disabled =
                chosen ||
                item.upload_status !== STATUS_COMPLETED ||
                (!checked && picked.length >= limit);
              const id = `pick-${item.id}`;
              return (
                <li key={item.id} className="flex items-center gap-3 px-3 py-2 text-sm">
                  <Checkbox
                    id={id}
                    checked={checked}
                    disabled={disabled}
                    onCheckedChange={(value) => toggle(item, value === true)}
                  />
                  <label htmlFor={id} className="flex min-w-0 flex-1 items-center gap-2">
                    <FileIcon className="h-4 w-4 shrink-0 text-slate-400" aria-hidden />
                    <span className="truncate">{item.name}</span>
                  </label>
                  <span className="shrink-0 text-muted-foreground">
                    {item.upload_status === STATUS_COMPLETED
                      ? formatBytes(item.size)
                      : t('processing')}
                  </span>
                </li>
              );
            })}
          </ul>
        )}

        {pagination.lastPage > 1 && (
          <Pagination
            pagination={pagination}
            page={page}
            onPageChange={setPage}
            perPage={perPage}
            onPerPageChange={(value) => {
              setPerPage(value);
              setPage(1);
            }}
          />
        )}
      </div>
    </AppDialog>
  );
}
