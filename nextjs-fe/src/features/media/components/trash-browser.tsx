'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useTranslations } from 'next-intl';
import { ArrowLeft, RotateCcw, Trash2 } from 'lucide-react';
import { DataTable, type Column } from '@/components/common/data-table/data-table';
import { Pagination } from '@/components/common/data-table/pagination';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Input } from '@/components/ui/input';
import { ADMIN_ROUTES } from '@/shared/config';
import { usePermissions } from '@/shared/hooks/use-permissions';
import { getApiErrorMessage, notification } from '@/shared/utils';
import { formatTimestamp } from '@/shared/utils/date-formatter';
import type { TrashItem } from '@/shared/types/models';
import { MEDIA_PER_PAGE } from '../hooks/use-media';
import { useTrashActions, useTrashList } from '../hooks/use-trash';
import { formatBytes } from '../lib/media-paths';
import { FileThumb } from './file-thumb';

const DAY_MS = 24 * 60 * 60 * 1000;

/**
 * Trash of the file manager (MEDIA-07): deleted files, restore (into the old folder, recreated if
 * needed), delete for good, empty. Buttons follow the admin's permissions.
 */
export function TrashBrowser() {
  const t = useTranslations('media.trash');
  const tMedia = useTranslations('media');
  const tCommon = useTranslations('common');
  const { can } = usePermissions();
  const [name, setName] = useState('');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(MEDIA_PER_PAGE);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [purgeIds, setPurgeIds] = useState<number[]>([]);
  const [emptying, setEmptying] = useState(false);

  const { data, loading, pagination } = useTrashList({ name, page, perPage });
  const actions = useTrashActions();
  const canRestore = can('media.trash-restore');
  const canPurge = can('media.trash-delete');
  const canEmpty = can('media.trash-empty');

  const restore = async (item: TrashItem) => {
    try {
      const file = await actions.restore.mutateAsync(item.id);
      notification.success(t('restored', { path: file.path }));
    } catch (error) {
      notification.error(getApiErrorMessage(error) ?? t('restoreFailed'));
    }
  };

  const confirmPurge = async () => {
    try {
      await actions.purge.mutateAsync(purgeIds);
      notification.success(t('deleted'));
      setSelectedIds((ids) => ids.filter((id) => !purgeIds.includes(id)));
      setPurgeIds([]);
    } catch (error) {
      notification.error(getApiErrorMessage(error) ?? t('failed'));
    }
  };

  const confirmEmpty = async () => {
    try {
      await actions.empty.mutateAsync();
      notification.success(t('emptied'));
      setSelectedIds([]);
      setEmptying(false);
    } catch (error) {
      notification.error(getApiErrorMessage(error) ?? t('failed'));
    }
  };

  const columns: Column<TrashItem>[] = [
    {
      key: 'name',
      label: tMedia('name'),
      render: (item) => (
        <span className="flex items-center gap-2">
          <FileThumb item={item} />
          <span className="truncate">{item.name}</span>
        </span>
      ),
    },
    { key: 'path', label: t('originalPath'), render: (item) => item.path },
    { key: 'size', label: tMedia('size'), render: (item) => formatBytes(item.size) },
    {
      key: 'deleted_at',
      label: t('deletedAt'),
      render: (item) => formatTimestamp(item.deleted_at),
    },
    { key: 'deleted_by', label: t('deletedBy'), render: (item) => item.deleted_by ?? '' },
    {
      key: 'expires_at',
      label: t('expires'),
      render: (item) =>
        item.expires_at
          ? t('daysLeft', {
              count: Math.max(0, Math.ceil((Date.parse(item.expires_at) - Date.now()) / DAY_MS)),
            })
          : '',
    },
    {
      key: 'actions',
      label: tCommon('actions'),
      render: (item) => (
        <div className="flex items-center gap-1">
          {canRestore && (
            <Button
              variant="outline"
              size="sm"
              disabled={actions.restore.isPending}
              onClick={() => void restore(item)}
            >
              <RotateCcw className="mr-1 h-3 w-3" />
              {t('restore')}
            </Button>
          )}
          {canPurge && (
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-8"
              aria-label={t('deleteForever')}
              title={t('deleteForever')}
              onClick={() => setPurgeIds([item.id])}
            >
              <Trash2 className="h-4 w-4 text-red-600" />
            </Button>
          )}
        </div>
      ),
    },
  ];

  return (
    <div className="mt-6 space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <Button variant="ghost" asChild>
          <Link href={ADMIN_ROUTES.FILES}>
            <ArrowLeft className="mr-2 h-4 w-4" />
            {t('back')}
          </Link>
        </Button>
        <div className="flex flex-wrap items-center gap-2">
          <Input
            className="w-48"
            placeholder={tMedia('filterPlaceholder')}
            aria-label={tMedia('filterPlaceholder')}
            value={name}
            onChange={(event) => {
              setName(event.target.value);
              setPage(1);
            }}
          />
          {canPurge && selectedIds.length > 0 && (
            <Button variant="destructive" onClick={() => setPurgeIds(selectedIds)}>
              <Trash2 className="mr-2 h-4 w-4" />
              {t('deleteForever')} ({selectedIds.length})
            </Button>
          )}
          {canEmpty && data.length > 0 && (
            <Button variant="destructive" onClick={() => setEmptying(true)}>
              {t('empty')}
            </Button>
          )}
        </div>
      </div>

      {!loading && data.length === 0 ? (
        <p className="py-8 text-center text-sm text-muted-foreground">{t('nothing')}</p>
      ) : (
        <DataTable
          data={data}
          columns={columns}
          loading={loading}
          showActions={false}
          selectedIds={canPurge ? selectedIds : undefined}
          onSelectionChange={canPurge ? setSelectedIds : undefined}
        />
      )}
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

      <ConfirmDialog
        open={purgeIds.length > 0}
        onOpenChange={(open) => !open && setPurgeIds([])}
        title={t('deleteTitle', { count: purgeIds.length })}
        description={t('deleteDescription')}
        onConfirm={confirmPurge}
        confirmText={t('deleteForever')}
        variant="destructive"
        isLoading={actions.purge.isPending}
      />
      <ConfirmDialog
        open={emptying}
        onOpenChange={setEmptying}
        title={t('emptyTitle')}
        description={t('emptyDescription')}
        onConfirm={confirmEmpty}
        confirmText={t('empty')}
        variant="destructive"
        isLoading={actions.empty.isPending}
      />
    </div>
  );
}
