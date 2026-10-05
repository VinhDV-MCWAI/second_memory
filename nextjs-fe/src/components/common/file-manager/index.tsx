'use client';

import { useState } from 'react';
import { Sidebar } from './sidebar';
import { Breadcrumb } from './breadcrumb';
import { Toolbar } from './toolbar';
import { FileGrid } from './file-grid';
import { FileList } from './file-list';
import { PreviewModal } from './preview-modal';
import { Pagination } from './pagination';
import { useFileManager } from '@/shared/hooks/use-file-manager';
import { buildBreadcrumb } from './utils';
import { UploadDialog } from './dialogs/upload-dialog';
import { NewFolderDialog } from './dialogs/new-folder-dialog';
import { RenameDialog } from './dialogs/rename-dialog';
import { DeleteConfirmDialog } from './dialogs/delete-confirm-dialog';
import { MoveCopyDialog } from './dialogs/move-copy-dialog';
import { HeavyUploadNotification } from '@/components/common/HeavyUploadNotification';
import type { MediaFile, FilterType, SortField, MoveCopyMode } from '@/shared/types/file-manager.types';
import toast from 'react-hot-toast';
import { useTranslations } from 'next-intl';
import { SORT_ORDER } from '@/shared/config/constant';
import { FILE_MANAGER_SORT_FIELDS, FILTER_TYPE, VIEW_MODE, FILE_TYPE, INITIAL_PAGINATION, MOVE_COPY_MODE } from '@/shared/config/constant';

export default function FileManager() {
  const t = useTranslations();
  const {
    currentPath,
    viewMode,
    files,
    selectedFiles,
    isLoading,
    searchQuery,
    filterType,
    sortBy,
    pagination,
    setCurrentPath,
    setViewMode,
    toggleFileSelection,
    selectAllFiles,
    setSearchQuery,
    setFilterOptions,
    setSortOptions,
    setPagination,
    createFolder,
    uploadFiles,
    deleteFiles,
    renameFile,
    moveFiles,
    copyFiles,
    heavyUploads,
    clearHeavyUpload,
    refreshFiles,
  } = useFileManager();

  // Dialog states
  const [isUploadOpen, setIsUploadOpen] = useState(false);
  const [isNewFolderOpen, setIsNewFolderOpen] = useState(false);
  const [isRenameOpen, setIsRenameOpen] = useState(false);
  const [isDeleteOpen, setIsDeleteOpen] = useState(false);
  const [isMoveCopyOpen, setIsMoveCopyOpen] = useState(false);
  const [moveCopyMode, setMoveCopyMode] = useState<MoveCopyMode>(MOVE_COPY_MODE.MOVE);

  // Selection state for operations
  const [targetFile, setTargetFile] = useState<MediaFile | null>(null);
  const [previewFile, setPreviewFile] = useState<MediaFile | null>(null);

  const breadcrumbItems = buildBreadcrumb(currentPath);

  // Handlers
  const handleFileClick = (file: MediaFile) => {
    if (file.type === FILE_TYPE.FOLDER) {
      setCurrentPath(file.folder_path === '/' ? `/${file.name}` : `${file.folder_path}/${file.name}`);
    } else {
      setPreviewFile(file);
    }
  };

  const handleNavigate = (path: string) => {
    setCurrentPath(path);
  };

  const handleUpload = async (filesToUpload: File[]) => {
    try {
      if (uploadFiles) {
        await uploadFiles(filesToUpload);
      } else {
        toast.error(t('fileManager.featureUnavailable'));
      }
    } catch {
      // Error handled in hook
    }
  };

  const handleCreateFolder = async (name: string) => {
    try {
      if (createFolder) {
        await createFolder(name);
      } else {
        toast.error(t('fileManager.featureUnavailable'));
      }
    } catch {
      // Error handled in hook
    }
  };

  const handleRename = (file: MediaFile) => {
    setTargetFile(file);
    setIsRenameOpen(true);
  };

  const handleRenameSubmit = async (file: MediaFile, newName: string) => {
    try {
      if (renameFile) {
        await renameFile(file.id, newName);
      } else {
        toast.error(t('fileManager.featureUnavailable'));
      }
    } catch {
      // Error handled in hook
    }
  };

  const handleDelete = (file?: MediaFile) => {
    if (file) {
      setTargetFile(file);
      // If deleting a single file that isn't in selection, clear selection first
      if (!selectedFiles.includes(file.id)) {
        // Optional: clear selection or just delete this one
        // For simplicity, let's just set targetFile and handle logic in confirm
      }
    } else {
      setTargetFile(null);
    }
    setIsDeleteOpen(true);
  };

  const handleDeleteConfirm = async () => {
    try {
      const idsToDelete = targetFile ? [targetFile.id] : selectedFiles;
      if (deleteFiles) {
        await deleteFiles(idsToDelete);
      } else {
        toast.error(t('fileManager.featureUnavailable'));
      }
      setTargetFile(null);
    } catch {
      // Error handled in hook
    }
  };

  const handleMove = (file?: MediaFile) => {
    setMoveCopyMode(MOVE_COPY_MODE.MOVE);
    if (file) setTargetFile(file);
    else setTargetFile(null);
    setIsMoveCopyOpen(true);
  };

  const handleCopy = (file?: MediaFile) => {
    setMoveCopyMode(MOVE_COPY_MODE.COPY);
    if (file) setTargetFile(file);
    else setTargetFile(null);
    setIsMoveCopyOpen(true);
  };

  const handleMoveCopyConfirm = async (targetPath: string) => {
    try {
      const idsToProcess = targetFile ? [targetFile.id] : selectedFiles;
      if (moveCopyMode === MOVE_COPY_MODE.MOVE) {
        if (moveFiles) {
          await moveFiles(idsToProcess, targetPath);
        } else {
          toast.error(t('fileManager.featureUnavailable'));
        }
      } else {
        if (copyFiles) {
          await copyFiles(idsToProcess, targetPath);
        } else {
          toast.error(t('fileManager.featureUnavailable'));
        }
      }
      setTargetFile(null);
    } catch {
      // Error handled in hook
    }
  };

  const handleDownload = (file: MediaFile) => {
    // In a real app, this would trigger a download
    // For mock, we just open the URL
    const link = document.createElement('a');
    link.href = file.url;
    link.download = file.name;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // Preview Navigation
  const handlePreviewNext = () => {
    if (!previewFile) return;
    const currentIndex = files.findIndex(f => f.id === previewFile.id);
    if (currentIndex < files.length - 1) {
      setPreviewFile(files[currentIndex + 1]);
    }
  };

  const handlePreviewPrev = () => {
    if (!previewFile) return;
    const currentIndex = files.findIndex(f => f.id === previewFile.id);
    if (currentIndex > 0) {
      setPreviewFile(files[currentIndex - 1]);
    }
  };

  const hasNextPreview = previewFile
    ? files.findIndex(f => f.id === previewFile.id) < files.length - 1
    : false;
  const hasPrevPreview = previewFile
    ? files.findIndex(f => f.id === previewFile.id) > 0
    : false;

  return (
    <div className="flex h-[calc(100vh-4rem)] overflow-hidden bg-background">
      <Sidebar
        currentPath={currentPath}
        onPathChange={setCurrentPath}
        className="hidden w-64 border-r border-border md:block"
      />

      <div className="flex flex-1 flex-col overflow-hidden">
        <Breadcrumb items={breadcrumbItems} onNavigate={handleNavigate} />

        <Toolbar
          viewMode={viewMode}
          onViewModeChange={setViewMode}
          onUpload={() => setIsUploadOpen(true)}
          onNewFolder={() => setIsNewFolderOpen(true)}
          onDelete={() => handleDelete()}
          onMove={() => handleMove()}
          onCopy={() => handleCopy()}

          onSearchChange={setSearchQuery}
          searchQuery={searchQuery}
          selectedCount={selectedFiles.length}
          filterOptions={{ type: (filterType as FilterType) || FILTER_TYPE.ALL }}
          onFilterChange={(opts) => setFilterOptions?.(opts)}
          sortOptions={{ field: (sortBy as SortField) || FILE_MANAGER_SORT_FIELDS.NAME, order: SORT_ORDER.ASC }}
          onSortChange={(opts) => setSortOptions?.(opts)}
        />

        <div className="flex-1 overflow-auto p-4">
          {viewMode === VIEW_MODE.GRID ? (
            <FileGrid
              files={files}
              selectedFiles={selectedFiles}
              onSelect={(id, val) => toggleFileSelection?.(id, val)}
              onFileClick={handleFileClick}
              onNavigate={handleNavigate}
              isLoading={isLoading}
              onPreview={setPreviewFile}
              onRename={handleRename}
              onMove={handleMove}
              onCopy={handleCopy}
              onDelete={handleDelete}
              onDownload={handleDownload}
            />
          ) : (
            <FileList
              files={files}
              selectedFiles={selectedFiles}
              onSelect={(id, val) => toggleFileSelection?.(id, val)}
              onSelectAll={(val) => selectAllFiles?.(val)}
              onFileClick={handleFileClick}
              onNavigate={handleNavigate}
              isLoading={isLoading}
              sortField={(sortBy as SortField) || FILE_MANAGER_SORT_FIELDS.NAME}
              sortOrder={SORT_ORDER.ASC}
              onSort={(field) => setSortOptions?.({
                field: field as SortField,
                order: SORT_ORDER.ASC
              })}
              onPreview={setPreviewFile}
              onRename={handleRename}
              onMove={handleMove}
              onCopy={handleCopy}
              onDelete={handleDelete}
              onDownload={handleDownload}
            />
          )}
        </div>

        <Pagination
          pagination={pagination || INITIAL_PAGINATION}
          onPageChange={(page) => setPagination?.({ ...(pagination || INITIAL_PAGINATION), page })}
        />
      </div>

      {/* Dialogs */}
      <UploadDialog
        open={isUploadOpen}
        onOpenChange={setIsUploadOpen}
        onUpload={handleUpload}
        currentPath={currentPath}
      />

      <NewFolderDialog
        open={isNewFolderOpen}
        onOpenChange={setIsNewFolderOpen}
        onCreateFolder={handleCreateFolder}
      />

      <RenameDialog
        open={isRenameOpen}
        onOpenChange={setIsRenameOpen}
        file={targetFile}
        onRename={handleRenameSubmit}
      />

      <DeleteConfirmDialog
        open={isDeleteOpen}
        onOpenChange={setIsDeleteOpen}
        onConfirm={handleDeleteConfirm}
        count={targetFile ? 1 : selectedFiles.length}
        itemName={targetFile?.name}
      />

      <MoveCopyDialog
        open={isMoveCopyOpen}
        onOpenChange={setIsMoveCopyOpen}
        mode={moveCopyMode}
        count={targetFile ? 1 : selectedFiles.length}
        onConfirm={handleMoveCopyConfirm}
        currentPath={currentPath}
        selectedFileIds={targetFile ? [targetFile.id] : selectedFiles}
      />

      <PreviewModal
        file={previewFile}
        onClose={() => setPreviewFile(null)}
        onDelete={(file) => handleDelete(file)}
        onNext={handlePreviewNext}
        onPrev={handlePreviewPrev}
        hasNext={hasNextPreview}
        hasPrev={hasPrevPreview}
      />

      {/* Heavy Upload Notifications - WebSocket listeners for each heavy upload */}
      {heavyUploads?.map((upload) => (
        <HeavyUploadNotification
          key={upload.roomId}
          roomId={upload.roomId}
          fileName={upload.fileName}
          mediaId={upload.mediaId}
          onComplete={() => {
            // Remove from state and refresh file list
            if (clearHeavyUpload) clearHeavyUpload(upload.roomId);
            if (refreshFiles) refreshFiles();
          }}
        />
      ))}
    </div>
  );
}

// Named exports for convenience
export { FileManager };
export { FileManagerContent } from './file-manager-content';
