import { useState, useEffect, useCallback } from 'react';
import {
  MediaFile,
  ViewMode,
  FilterOptions,
  SortOptions,
  PaginationState,
  FileManagerContextType,
  FilterType,
  SortField,
} from '@/features/media/types/file-manager.types';
import { mediaFileService } from '@/features/media/services/media-file.service';
import toast from 'react-hot-toast';
import {
  FILTER_TYPE,
  FILE_MANAGER_SORT_FIELDS,
  SORT_ORDER,
  INITIAL_PAGINATION,
  PAGINATION,
} from '@/shared/config';
import { useTranslations } from 'next-intl';
import { extractList, processFiles, toMediaFile } from '@/features/media/utils/file-list';
import { uploadMediaFile, type HeavyUpload } from '@/features/media/utils/upload-media-file';

export const useFileManager = (): FileManagerContextType => {
  const t = useTranslations('fileManager');
  const [currentPath, setCurrentPath] = useState('/');
  const [viewMode, setViewMode] = useState<ViewMode>('grid');
  const [rawFiles, setRawFiles] = useState<MediaFile[]>([]);
  const [files, setFiles] = useState<MediaFile[]>([]);
  const [selectedFiles, setSelectedFiles] = useState<string[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  const [filterOptions, setFilterOptions] = useState<FilterOptions>({
    type: FILTER_TYPE.ALL,
  });

  const [sortOptions, setSortOptions] = useState<SortOptions>({
    field: FILE_MANAGER_SORT_FIELDS.DATE,
    order: SORT_ORDER.DESC,
  });

  const [pagination, setPagination] = useState<PaginationState>(INITIAL_PAGINATION);

  // State to track heavy file uploads for WebSocket notifications
  // Note: Not persisted to localStorage - if user refreshes/closes tab,
  // MinIO lifecycle will auto-cleanup incomplete parts and user can re-upload if needed
  const [heavyUploads, setHeavyUploads] = useState<HeavyUpload[]>([]);

  // Fetch files from API (only when path changes)
  const fetchFiles = useCallback(
    async (signal?: AbortSignal) => {
      setIsLoading(true);
      try {
        const response = await mediaFileService.list(
          {
            parent_path: currentPath || '/',
          },
          { signal },
        );

        // If the signal is aborted, stop processing
        if (signal?.aborted) {
          return;
        }

        setRawFiles(extractList(response).map(toMediaFile));
      } catch {
        if (signal?.aborted) return;
        toast.error(t('listError'));
        setRawFiles([]);
      } finally {
        if (!signal?.aborted) {
          setIsLoading(false);
        }
      }
    },
    [currentPath, t],
  );

  // Fetch files only when path changes
  useEffect(() => {
    const controller = new AbortController();
    fetchFiles(controller.signal);

    return () => {
      controller.abort();
    };
  }, [currentPath, fetchFiles]);

  // Apply client-side filtering, sorting, and searching
  useEffect(() => {
    setFiles(processFiles(rawFiles, searchQuery, filterOptions, sortOptions));
  }, [rawFiles, searchQuery, filterOptions, sortOptions]);

  // Reset pagination when filters change
  useEffect(() => {
    setPagination((prev) => ({ ...prev, page: PAGINATION.DEFAULT_PAGE }));
  }, [searchQuery, filterOptions]);

  // Clear selection when path changes
  useEffect(() => {
    setSelectedFiles([]);
  }, [currentPath]);

  const refreshFiles = useCallback(async () => {
    await fetchFiles();
  }, [fetchFiles]);

  const toggleFileSelection = useCallback((fileId: string, selected: boolean) => {
    setSelectedFiles((prev) => {
      if (selected) {
        // Add to selection if not already selected
        return prev.includes(fileId) ? prev : [...prev, fileId];
      } else {
        // Remove from selection
        return prev.filter((id) => id !== fileId);
      }
    });
  }, []);

  const selectAllFiles = useCallback(
    (selected: boolean) => {
      if (selected) {
        // Select all files
        setSelectedFiles(files.map((f) => f.id));
      } else {
        // Deselect all
        setSelectedFiles([]);
      }
    },
    [files],
  );

  const createFolder = useCallback(
    async (name: string) => {
      try {
        await mediaFileService.createFolder({
          name,
          parent_path: currentPath, // Send current path as parent_path
        });
        toast.success(t('createFolderSuccess'));
        await fetchFiles();
      } catch {
        toast.error(t('createFolderError'));
      }
    },
    [currentPath, fetchFiles, t],
  );

  const uploadFiles = useCallback(
    async (filesToUpload: File[]) => {
      try {
        setIsLoading(true);
        // Upload in parallel; each file reports its own errors
        const results = await Promise.all(
          filesToUpload.map((file) => uploadMediaFile(file, currentPath, t)),
        );

        // Heavy uploads finish later: the parent subscribes to their WebSocket rooms
        const heavyFileUploads = results.flatMap((r) => (r.heavy ? [r.heavy] : []));
        if (heavyFileUploads.length > 0) {
          setHeavyUploads((prev) => [...prev, ...heavyFileUploads]);
        }

        // Show success for files that were processed immediately (not heavy uploads)
        const immediateUploads = results.filter((r) => r.type === 'immediate').length;
        const failedUploads = results.filter((r) => r.type === 'error').length;

        if (immediateUploads > 0) {
          toast.success(`${t('uploadSuccess')} ${immediateUploads} file(s)`);
        }

        if (failedUploads > 0) {
          toast.error(`${failedUploads} file(s) failed to upload`);
        }

        // Note: Don't fetchFiles here for heavy uploads - will be refreshed when WSS notification arrives
        // Only fetch if there were immediate uploads
        if (immediateUploads > 0) {
          await fetchFiles();
        }
      } catch (error) {
        console.error('Upload error sequence:', error);
        // Extract message if possible
        const msg = error instanceof Error ? error.message : t('uploadError');
        toast.error(`${t('uploadError')}: ${msg}`);
        throw error;
      } finally {
        setIsLoading(false);
      }
    },
    [currentPath, fetchFiles, t],
  );

  const deleteFiles = useCallback(
    async (ids: string[]) => {
      try {
        await mediaFileService.delete({ ids: ids.map((id) => Number(id)) });
        setSelectedFiles((prev) => prev.filter((id) => !ids.includes(id)));
        toast.success(t('deleteSuccess'));
        await fetchFiles();
      } catch {
        toast.error(t('deleteError'));
      }
    },
    [fetchFiles, t],
  );

  const renameFile = useCallback(
    async (id: string, newName: string) => {
      try {
        await mediaFileService.rename(Number(id), { name: newName });
        toast.success(t('renameSuccess'));
        await fetchFiles();
      } catch {
        toast.error(t('renameError'));
      }
    },
    [fetchFiles, t],
  );

  const moveFiles = useCallback(
    async (ids: string[], targetPath: string) => {
      try {
        for (const id of ids) {
          await mediaFileService.move(Number(id), { new_parent_path: targetPath });
        }
        setSelectedFiles((prev) => prev.filter((id) => !ids.includes(id)));
        toast.success(t('moveSuccess'));
        await fetchFiles();
      } catch {
        toast.error(t('moveError'));
      }
    },
    [fetchFiles, t],
  );

  const copyFiles = useCallback(
    async (ids: string[], targetPath: string) => {
      try {
        await mediaFileService.copy({
          ids: ids.map((id) => Number(id)),
          target_folder_path: targetPath,
        });
        toast.success(t('copySuccess'));
        await fetchFiles();
      } catch {
        toast.error(t('copyError'));
      }
    },
    [fetchFiles, t],
  );

  // Clear a specific heavy upload from the list (after WSS notification received)
  const clearHeavyUpload = useCallback((roomId: string) => {
    setHeavyUploads((prev) => prev.filter((upload) => upload.roomId !== roomId));
  }, []);

  return {
    currentPath,
    setCurrentPath,
    viewMode,
    setViewMode,
    files,
    setFiles,
    selectedFiles,
    setSelectedFiles,
    isLoading,
    setIsLoading,
    searchQuery,
    setSearchQuery,
    filterOptions,
    setFilterOptions,
    sortOptions,
    setSortOptions,
    pagination,
    setPagination,
    refreshFiles,
    toggleFileSelection,
    selectAllFiles,
    createFolder,
    uploadFiles,
    deleteFiles,
    renameFile,
    moveFiles,
    filterType: filterOptions.type,
    setFilterType: (type: string) =>
      setFilterOptions((prev) => ({ ...prev, type: type as FilterType })),
    sortBy: sortOptions.field,
    setSortBy: (field: SortField) => setSortOptions((prev) => ({ ...prev, field })),
    copyFiles,
    heavyUploads,
    clearHeavyUpload,
  };
};
