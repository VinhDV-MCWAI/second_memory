'use client';

import { useRef, useState, type DragEvent } from 'react';
import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { z } from 'zod';
import {
  ChevronRight,
  Download,
  Eye,
  Folder,
  FolderInput,
  FolderPlus,
  KeyRound,
  Lock,
  Pencil,
  Trash,
  Trash2,
  Upload,
} from 'lucide-react';
import { DataTable, type Column } from '@/components/common/data-table/data-table';
import { Pagination } from '@/components/common/data-table/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Input } from '@/components/ui/input';
import { ADMIN_ROUTES, SORT_ORDER, type SortOrder } from '@/shared/config';
import { usePermissions } from '@/shared/hooks/use-permissions';
import { getApiErrorMessage, notification } from '@/shared/utils';
import { formatTimestamp } from '@/shared/utils/date-formatter';
import type { MediaItem, UnfinishedUpload } from '@/shared/types/models';
import {
  MEDIA_PER_PAGE,
  useMediaActions,
  useMediaList,
  useUnfinishedUploads,
} from '../hooks/use-media';
import { useUploads, type UploadState } from '../hooks/use-uploads';
import { discardUpload } from '../lib/uploader';
import {
  crumbsOf,
  filesHref,
  formatBytes,
  formatFileSize,
  parentOf,
  ROOT,
} from '../lib/media-paths';
import { IconButton } from './icon-button';
import { FileThumb } from './file-thumb';
import { FolderAccessDialog } from './folder-access-dialog';
import { MediaFieldDialog } from './media-field-dialog';
import { PreviewDialog } from './preview-dialog';
import { UnfinishedUploads } from './unfinished-uploads';
import { UploadPanel } from './upload-panel';

/** laravel-api MediaConst::NAME_MAX / UploadStatus::PROCESSING. */
const NAME_MAX = 255;
const STATUS_PROCESSING = 1;
const ACTIVE: UploadState[] = ['waiting', 'uploading', 'processing'];

type DialogState =
  | { kind: 'folder' }
  | { kind: 'rename'; item: MediaItem }
  | { kind: 'move'; item: MediaItem }
  | null;

/**
 * File manager (ADR-0014): browse folders (the folder is in the URL, so back/forward work), upload by
 * button or drag-and-drop, download, rename, move, delete. Buttons follow the admin's permissions.
 */
export function MediaBrowser() {
  const t = useTranslations('media');
  const tCommon = useTranslations('common');
  const router = useRouter();
  const folder = useSearchParams().get('folder') || ROOT;
  const { can } = usePermissions();

  const [name, setName] = useState('');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(MEDIA_PER_PAGE);
  const [sortBy, setSortBy] = useState('original_name');
  const [sortOrder, setSortOrder] = useState<SortOrder>(SORT_ORDER.ASC);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [dialog, setDialog] = useState<DialogState>(null);
  const [deleteIds, setDeleteIds] = useState<number[]>([]);
  const [previewing, setPreviewing] = useState<MediaItem | null>(null);
  const [accessFor, setAccessFor] = useState<MediaItem | null>(null);
  const [dragging, setDragging] = useState(false);
  const fileInput = useRef<HTMLInputElement>(null);

  const { data, loading, pagination } = useMediaList({
    folder,
    name,
    page,
    perPage,
    sortBy,
    sortOrder,
  });
  const actions = useMediaActions();
  const canUpload = can('media.upload-url') && can('media.multipart-start');
  const canResume = canUpload && can('media.multipart-unfinished');
  const unfinishedQuery = useUnfinishedUploads(canResume);
  const refreshUnfinished = () => {
    if (canResume) void unfinishedQuery.refetch();
  };
  const uploads = useUploads(() => void actions.refresh(), refreshUnfinished);
  // Uploads running in this tab are shown by the upload panel, not as unfinished
  const running = uploads.items
    .filter((item) => ACTIVE.includes(item.state))
    .map((item) => item.uploadId);
  const unfinished = (unfinishedQuery.data ?? []).filter((upload) => !running.includes(upload.id));
  const canUpdate = can('media.update');
  const canPreview = (item: MediaItem) => item.preview !== null && can('media.download-url');
  const canDelete = can('media.delete');
  // The dialog lists departments by name
  const canGrant = can('media.access') && can('department.list');
  const isLocked = (item: MediaItem) => (item.department_ids?.length ?? 0) > 0;

  const nameSchema = z
    .string()
    .trim()
    .min(1, t('validation.nameRequired'))
    .max(NAME_MAX, t('validation.nameTooLong', { max: NAME_MAX }))
    .refine(
      (value) => !value.includes('/') && value !== '.' && value !== '..',
      t('validation.nameInvalid'),
    );
  const folderSchema = z
    .string()
    .trim()
    .regex(/^\/(?:[^/]+(?:\/[^/]+)*)?$/, t('validation.folderInvalid'));

  const openFolder = (path: string) => {
    setPage(1);
    setSelectedIds([]);
    router.push(filesHref(path));
  };

  const startUpload = (files: FileList | null) => {
    if (!files || files.length === 0) return;
    uploads.add(
      Array.from(files).map((file) => ({
        file,
        // The same file into the same folder continues its unfinished upload
        resumeId: unfinished.find(
          (upload) =>
            upload.name === file.name && upload.size === file.size && upload.folder === folder,
        )?.id,
      })),
      folder,
    );
  };

  const resumeUpload = (upload: UnfinishedUpload, file: File) => {
    if (file.name !== upload.name || file.size !== upload.size) {
      notification.error(
        t('unfinished.wrongFile', { name: upload.name, size: formatBytes(upload.size) }),
      );
      return;
    }
    uploads.add([{ file, resumeId: upload.id }], upload.folder);
  };

  const discard = async (upload: UnfinishedUpload) => {
    try {
      await discardUpload(upload.id);
      notification.success(t('unfinished.discarded'));
    } catch (error) {
      notification.error(getApiErrorMessage(error) ?? t('unfinished.discardFailed'));
    } finally {
      refreshUnfinished();
    }
  };

  const onDrop = (event: DragEvent) => {
    event.preventDefault();
    setDragging(false);
    if (canUpload) startUpload(event.dataTransfer.files);
  };

  const download = async (item: MediaItem) => {
    try {
      await actions.download(item.id);
    } catch (error) {
      notification.error(getApiErrorMessage(error) ?? t('downloadFailed'));
    }
  };

  const confirmDelete = async () => {
    try {
      await actions.remove.mutateAsync(deleteIds);
      notification.success(t('deleted'));
      setSelectedIds((ids) => ids.filter((id) => !deleteIds.includes(id)));
      setDeleteIds([]);
    } catch (error) {
      notification.error(getApiErrorMessage(error) ?? tCommon('failedToDelete'));
    }
  };

  const columns: Column<MediaItem>[] = [
    {
      key: 'original_name',
      label: t('name'),
      sortable: true,
      render: (item) =>
        item.is_file && canPreview(item) ? (
          <button
            type="button"
            className="flex items-center gap-2 hover:underline"
            onClick={() => setPreviewing(item)}
          >
            <FileThumb item={item} />
            <span className="truncate">{item.name}</span>
          </button>
        ) : item.is_file ? (
          <span className="flex items-center gap-2">
            <FileThumb item={item} />
            <span className="truncate">{item.name}</span>
          </span>
        ) : (
          <button
            type="button"
            className="flex items-center gap-2 font-medium text-blue-600 hover:underline dark:text-blue-400"
            onClick={() => openFolder(item.path)}
          >
            <Folder className="h-4 w-4 shrink-0" aria-hidden />
            <span className="truncate">{item.name}</span>
            {isLocked(item) && (
              <span title={t('access.lockedIcon')} className="shrink-0">
                <Lock
                  className="h-3.5 w-3.5 text-amber-600"
                  role="img"
                  aria-label={t('access.lockedIcon')}
                />
              </span>
            )}
          </button>
        ),
    },
    {
      key: 'size',
      label: t('size'),
      sortable: true,
      render: (item) => (item.is_file ? formatFileSize(item) : ''),
    },
    {
      key: 'updated_at',
      label: t('updated'),
      sortable: true,
      render: (item) => formatTimestamp(item.updated_at),
    },
    {
      key: 'actions',
      label: tCommon('actions'),
      render: (item) => (
        <div className="flex items-center gap-1">
          {item.upload_status === STATUS_PROCESSING ? (
            <Badge variant="secondary">{t('processing')}</Badge>
          ) : (
            item.is_file &&
            can('media.download-url') && (
              <>
                {canPreview(item) && (
                  <IconButton label={t('preview.open')} onClick={() => setPreviewing(item)}>
                    <Eye className="h-4 w-4" />
                  </IconButton>
                )}
                <IconButton label={t('download')} onClick={() => download(item)}>
                  <Download className="h-4 w-4" />
                </IconButton>
              </>
            )
          )}
          {canUpdate && (
            <>
              <IconButton label={t('rename')} onClick={() => setDialog({ kind: 'rename', item })}>
                <Pencil className="h-4 w-4" />
              </IconButton>
              <IconButton label={t('move')} onClick={() => setDialog({ kind: 'move', item })}>
                <FolderInput className="h-4 w-4" />
              </IconButton>
            </>
          )}
          {canGrant && !item.is_file && (
            <IconButton label={t('access.open')} onClick={() => setAccessFor(item)}>
              <KeyRound className="h-4 w-4" />
            </IconButton>
          )}
          {canDelete && (
            <IconButton label={tCommon('delete')} onClick={() => setDeleteIds([item.id])}>
              <Trash2 className="h-4 w-4 text-red-600" />
            </IconButton>
          )}
        </div>
      ),
    },
  ];

  return (
    <div
      className="relative mt-6 space-y-4"
      onDragOver={(event) => {
        if (!canUpload) return;
        event.preventDefault();
        setDragging(true);
      }}
      onDragLeave={(event) => {
        if (!event.currentTarget.contains(event.relatedTarget as Node | null)) setDragging(false);
      }}
      onDrop={onDrop}
    >
      <div className="flex flex-wrap items-center justify-between gap-3">
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
        <div className="flex flex-wrap items-center gap-2">
          <Input
            className="w-48"
            placeholder={t('filterPlaceholder')}
            aria-label={t('filterPlaceholder')}
            value={name}
            onChange={(event) => {
              setName(event.target.value);
              setPage(1);
            }}
          />
          {canDelete && selectedIds.length > 0 && (
            <Button variant="destructive" onClick={() => setDeleteIds(selectedIds)}>
              <Trash2 className="mr-2 h-4 w-4" />
              {t('deleteSelected', { count: selectedIds.length })}
            </Button>
          )}
          {can('media.trash-list') && (
            <Button variant="outline" asChild>
              <Link href={ADMIN_ROUTES.FILES_TRASH}>
                <Trash className="mr-2 h-4 w-4" />
                {t('trash.open')}
              </Link>
            </Button>
          )}
          {can('media.store-folder') && (
            <Button variant="outline" onClick={() => setDialog({ kind: 'folder' })}>
              <FolderPlus className="mr-2 h-4 w-4" />
              {t('newFolder')}
            </Button>
          )}
          {canUpload && (
            <>
              <input
                ref={fileInput}
                type="file"
                multiple
                hidden
                data-testid="media-file-input"
                onChange={(event) => {
                  startUpload(event.target.files);
                  event.target.value = '';
                }}
              />
              <Button onClick={() => fileInput.current?.click()}>
                <Upload className="mr-2 h-4 w-4" />
                {t('upload')}
              </Button>
            </>
          )}
        </div>
      </div>

      {canUpload && <p className="text-xs text-muted-foreground">{t('dropHint')}</p>}

      {canUpload && (
        <UnfinishedUploads uploads={unfinished} onResume={resumeUpload} onDiscard={discard} />
      )}

      <UploadPanel
        items={uploads.items}
        onCancel={uploads.cancel}
        onClearFinished={uploads.clearFinished}
      />

      <DataTable
        data={data}
        columns={columns}
        loading={loading}
        showActions={false}
        sortBy={sortBy}
        onSort={(column) => {
          setSortOrder(
            column === sortBy && sortOrder === SORT_ORDER.ASC ? SORT_ORDER.DESC : SORT_ORDER.ASC,
          );
          setSortBy(column);
        }}
        selectedIds={canDelete ? selectedIds : undefined}
        onSelectionChange={canDelete ? setSelectedIds : undefined}
      />
      {pagination.total > MEDIA_PER_PAGE && (
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

      {dragging && (
        <div className="pointer-events-none absolute inset-0 flex items-center justify-center rounded-lg border-2 border-dashed border-blue-500 bg-blue-50/80 text-blue-700 dark:bg-blue-950/80 dark:text-blue-300">
          {t('dropNow')}
        </div>
      )}

      <MediaFieldDialog
        open={dialog?.kind === 'folder'}
        onOpenChange={(open) => !open && setDialog(null)}
        title={t('newFolder')}
        field="name"
        label={t('folderName')}
        initialValue=""
        submitLabel={t('create')}
        schema={nameSchema}
        onSubmit={(value) => actions.createFolder.mutateAsync({ folder, name: value })}
      />
      <MediaFieldDialog
        open={dialog?.kind === 'rename'}
        onOpenChange={(open) => !open && setDialog(null)}
        title={t('rename')}
        field="name"
        label={t('newName')}
        initialValue={dialog?.kind === 'rename' ? dialog.item.name : ''}
        submitLabel={tCommon('save')}
        schema={nameSchema}
        onSubmit={(value) =>
          dialog?.kind === 'rename'
            ? actions.update.mutateAsync({ id: dialog.item.id, name: value })
            : Promise.resolve()
        }
      />
      <MediaFieldDialog
        open={dialog?.kind === 'move'}
        onOpenChange={(open) => !open && setDialog(null)}
        title={t('move')}
        field="folder"
        label={t('targetFolder')}
        hint={t('targetFolderHint')}
        initialValue={dialog?.kind === 'move' ? parentOf(dialog.item.path) : folder}
        submitLabel={t('move')}
        schema={folderSchema}
        onSubmit={(value) =>
          dialog?.kind === 'move'
            ? actions.update.mutateAsync({ id: dialog.item.id, folder: value })
            : Promise.resolve()
        }
      />
      <FolderAccessDialog
        folder={accessFor}
        onClose={() => setAccessFor(null)}
        onSave={(id, departmentIds) => actions.setAccess.mutateAsync({ id, departmentIds })}
      />
      <PreviewDialog
        item={previewing}
        onClose={() => setPreviewing(null)}
        onDownload={(item) => void download(item)}
      />
      <ConfirmDialog
        open={deleteIds.length > 0}
        onOpenChange={(open) => !open && setDeleteIds([])}
        title={t('deleteTitle', { count: deleteIds.length })}
        description={t('deleteDescription')}
        onConfirm={confirmDelete}
        confirmText={tCommon('delete')}
        variant="destructive"
        isLoading={actions.remove.isPending}
      />
    </div>
  );
}
