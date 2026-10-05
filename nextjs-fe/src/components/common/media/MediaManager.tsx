'use client';

import React, { useState, useEffect, useCallback } from 'react';
import { mediaFileService } from '@/shared/services/modules/media-file.service';
import { FileUpload } from './FileUpload';
import type { MediaFile, ListFilesParams, FileType } from '@/shared/types/media-file.types';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTranslations } from 'next-intl';
import { PAGINATION } from '@/shared/config';
import { MEDIA_FILE_TYPES, MIME_TYPE_PREFIX } from '@/shared/config/constant';
import { KEYBOARD_KEYS } from '@/shared/config/constant';

export function MediaManager() {
  const t = useTranslations();
  const [files, setFiles] = useState<MediaFile[]>([]);
  const [loading, setLoading] = useState(false);
  const [currentPage, setCurrentPage] = useState<number>(PAGINATION.DEFAULT_PAGE);
  const [totalPages, setTotalPages] = useState<number>(PAGINATION.DEFAULT_PAGE);
  const [fileType, setFileType] = useState<FileType>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [showUpload, setShowUpload] = useState(false);
  const [selectedFiles, setSelectedFiles] = useState<Set<number>>(new Set());

  const loadFiles = useCallback(async () => {
    setLoading(true);
    try {
      const params: ListFilesParams = {
        page: currentPage,
        per_page: PAGINATION.DEFAULT_PER_PAGE,
        search: searchQuery || undefined,
        file_type: fileType !== MEDIA_FILE_TYPES[0] ? fileType : undefined,
      };

      const response = await mediaFileService.list(params);
      setFiles(response.data);
      setTotalPages(response.last_page ?? 1);
    } catch {
      alert(t('media.failedToLoadFiles'));
    } finally {
      setLoading(false);
    }
  }, [currentPage, fileType, searchQuery, t]);

  useEffect(() => {
    loadFiles();
  }, [currentPage, fileType, loadFiles]);

  const handleSearch = () => {
    setCurrentPage(PAGINATION.DEFAULT_PAGE);
    loadFiles();
  };

  const handleUploadSuccess = () => {
    setShowUpload(false);
    loadFiles();
  };

  const handleDelete = async (id: number) => {
    if (!confirm(t('media.deleteFileConfirm'))) return;

    try {
      await mediaFileService.deleteSingle(id);
      loadFiles();
    } catch {
      alert(t('media.failedToDeleteFile'));
    }
  };

  const handleBulkDelete = async () => {
    if (selectedFiles.size === 0) return;
    if (!confirm(t('media.deleteFilesConfirm', { count: selectedFiles.size }))) return;

    try {
      await mediaFileService.delete({ ids: Array.from(selectedFiles) });
      setSelectedFiles(new Set());
      loadFiles();
    } catch {
      alert(t('media.failedToDeleteFiles'));
    }
  };

  const toggleFileSelection = (id: number) => {
    const newSelection = new Set(selectedFiles);
    if (newSelection.has(id)) {
      newSelection.delete(id);
    } else {
      newSelection.add(id);
    }
    setSelectedFiles(newSelection);
  };

  return (
    <div className="mx-auto max-w-7xl px-6 py-6">
      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-3xl font-bold text-slate-900 dark:text-white">{t('media.manager')}</h1>
        <Button onClick={() => setShowUpload(!showUpload)}>
          {showUpload ? t('media.hideUpload') : t('media.uploadFile')}
        </Button>
      </div>

      {/* Upload Section */}
      {showUpload && (
        <div className="mb-8 rounded-lg bg-slate-50 p-6 dark:bg-slate-900">
          <FileUpload onUploadSuccess={handleUploadSuccess} />
        </div>
      )}

      {/* Toolbar */}
      <div className="mb-6 flex flex-wrap items-center gap-4">
        {/* Search Box */}
        <div className="flex gap-2">
          <Input
            type="text"
            placeholder={t('media.searchFiles')}
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            onKeyPress={(e) => e.key === KEYBOARD_KEYS.ENTER && handleSearch()}
            className="min-w-[250px]"
          />
          <Button onClick={handleSearch} variant="secondary" size="sm">
            {t('common.search')}
          </Button>
        </div>

        {/* Filter Tabs */}
        <div className="flex gap-2">
          {(MEDIA_FILE_TYPES as readonly FileType[]).map((type) => (
            <Button
              key={type}
              onClick={() => setFileType(type)}
              variant={fileType === type ? 'default' : 'outline'}
              size="sm"
            >
              {t(`media.${type}`)}
            </Button>
          ))}
        </div>

        {/* Bulk Delete */}
        {selectedFiles.size > 0 && (
          <Button onClick={handleBulkDelete} variant="destructive" size="sm">
            {t('media.deleteSelected', { count: selectedFiles.size })}
          </Button>
        )}
      </div>

      {/* Loading State */}
      {loading ? (
        <div className="py-16 text-center text-slate-500 dark:text-slate-400">
          <div className="mb-4 inline-block h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-blue-600 dark:border-slate-800 dark:border-t-blue-400" />
          <p>{t('common.loading')}</p>
        </div>
      ) : (
        <>
          {/* Files Grid */}
          <div className="mb-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {files.map((file) => (
              <Card key={file.id} className="group relative overflow-hidden transition-shadow hover:shadow-lg">
                {/* Checkbox */}
                <div className="absolute left-3 top-3 z-10">
                  <input
                    type="checkbox"
                    checked={selectedFiles.has(file.id)}
                    onChange={() => toggleFileSelection(file.id)}
                    className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                {/* File Preview */}
                <div className="flex h-48 items-center justify-center overflow-hidden bg-slate-100 dark:bg-slate-800">
                  {file.mime_type && file.mime_type.startsWith(MIME_TYPE_PREFIX.IMAGE) ? (
                    /* eslint-disable-next-line @next/next/no-img-element */
                    <img
                      src={file.url || ''}
                      alt={file.original_name}
                      className="h-full w-full object-cover"
                    />
                  ) : (
                    <div className="text-6xl">
                      {mediaFileService.getFileTypeIcon(file.mime_type || '')}
                    </div>
                  )}
                </div>

                {/* File Details */}
                <div className="p-4">
                  <p
                    className="mb-1 truncate font-semibold text-slate-900 dark:text-white"
                    title={file.original_name}
                  >
                    {file.original_name}
                  </p>
                  <p className="text-sm text-slate-500 dark:text-slate-400">
                    {mediaFileService.formatFileSize(file.size || 0)} • {new Date(file.created_at).toLocaleDateString()}
                  </p>
                </div>

                {/* File Actions */}
                <div className="flex justify-center gap-2 border-t border-slate-200 p-3 dark:border-slate-700">
                  <Button
                    onClick={() => window.open(file.url || '', '_blank')}
                    variant="ghost"
                    size="sm"
                    title={t('view')}
                  >
                    👁️
                  </Button>
                  <Button
                    onClick={() => mediaFileService.download(file.id, file.original_name)}
                    variant="ghost"
                    size="sm"
                    title={t('download')}
                  >
                    ⬇️
                  </Button>
                  <Button
                    onClick={() => handleDelete(file.id)}
                    variant="ghost"
                    size="sm"
                    title={t('delete')}
                  >
                    🗑️
                  </Button>
                </div>
              </Card>
            ))}
          </div>

          {/* Empty State */}
          {files.length === 0 && !loading && (
            <div className="py-16 text-center text-slate-500 dark:text-slate-400">
              <p className="text-lg">{t('common.noData')}</p>
            </div>
          )}

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex items-center justify-center gap-4">
              <Button
                onClick={() => setCurrentPage((p) => Math.max(PAGINATION.DEFAULT_PAGE, p - 1))}
                disabled={currentPage === PAGINATION.DEFAULT_PAGE}
                variant="outline"
                size="sm"
              >
                {t('previous')}
              </Button>
              <span className="text-slate-700 dark:text-slate-300">
                {t('pageOf', { page: currentPage, totalPages })}
              </span>
              <Button
                onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                disabled={currentPage === totalPages}
                variant="outline"
                size="sm"
              >
                {t('next')}
              </Button>
            </div>
          )}
        </>
      )}
    </div>
  );
}
