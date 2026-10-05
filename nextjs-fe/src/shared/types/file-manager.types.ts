/**
 * File Manager Types
 * Shared types for file manager functionality
 */

export type FileType = 'file' | 'folder';
export type ViewMode = 'grid' | 'list';
export type FileOperationType = 'rename' | 'move' | 'copy' | 'delete' | 'upload' | 'create_folder';
export type SortField = 'name' | 'date' | 'size' | 'type';
export type SortOrder = 'asc' | 'desc';
export type FilterType = 'all' | 'images' | 'videos' | 'documents' | 'folders';
export type MoveCopyMode = 'move' | 'copy';

export interface MediaFile {
  id: string;
  drive_id: string;
  name: string;
  mime_type: string;
  url: string;
  thumbnail_url?: string;
  folder_path: string;
  parent_id?: string;
  size: number;
  owner_id: string;
  created_at: string;
  updated_at: string;
  type: FileType;
  tags?: string[];
  isSelected?: boolean;
  upload_status?: number;
}

export interface Folder {
  id: string;
  name: string;
  path: string;
  parent_id?: string;
  children?: Folder[];
  file_count?: number;
}

export interface PaginationState {
  page: number;
  pageSize: number;
  total: number;
  totalPages: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  pagination: PaginationState;
}

export interface UploadProgress {
  fileId: string;
  fileName: string;
  progress: number; // 0-100
  status: 'pending' | 'uploading' | 'completed' | 'error';
  error?: string;
}

export interface UploadState {
  files: File[];
  progresses: UploadProgress[];
  isUploading: boolean;
}

export interface ExtendedFile extends File {
  tempKey?: string;
  isHeavyUploaded?: boolean;
  tempMetadata?: {
    original_name: string;
    extension: string;
    mime_type: string;
    size: number;
  };
}

export interface UploadedFileData {
  file: ExtendedFile | File;
  key: string;
  preview?: string;
  uploading: boolean;
  progress?: number;
  uploaded: boolean;
  error?: string;
  metadata: {
    original_name: string;
    extension: string;
    mime_type: string;
    size: number;
  };
}

export interface FilterOptions {
  type: FilterType;
  dateFrom?: Date;
  dateTo?: Date;
  minSize?: number;
  maxSize?: number;
  tags?: string[];
}

export interface SortOptions {
  field: SortField;
  order: SortOrder;
}

export interface FileOperation {
  type: FileOperationType;
  fileIds: string[];
  targetPath?: string;
  newName?: string;
  metadata?: Record<string, unknown>;
}

export interface FileManagerContextType {
  currentPath: string;
  setCurrentPath: (path: string) => void;
  viewMode: ViewMode;
  setViewMode: (mode: ViewMode) => void;
  files: MediaFile[];
  setFiles: (files: MediaFile[]) => void;
  selectedFiles: string[];
  setSelectedFiles: (ids: string[]) => void;
  isLoading: boolean;
  setIsLoading: (loading: boolean) => void;
  pagination?: PaginationState;
  setPagination?: (pagination: PaginationState) => void;
  searchQuery: string;
  setSearchQuery: (query: string) => void;
  filterType: string;
  setFilterType: (type: string) => void;
  sortBy: string;
  setSortBy: (sort: 'name' | 'date' | 'size' | 'type') => void;
  filterOptions?: FilterOptions;
  setFilterOptions?: (options: FilterOptions) => void;
  sortOptions?: SortOptions;
  setSortOptions?: (options: SortOptions) => void;
  
  // Actions
  refreshFiles?: () => Promise<void>;
  toggleFileSelection?: (fileId: string, selected: boolean) => void;
  selectAllFiles?: (selected: boolean) => void;
  createFolder?: (name: string) => Promise<void>;
  uploadFiles?: (files: File[]) => Promise<void>;
  deleteFiles?: (ids: string[]) => Promise<void>;
  renameFile?: (id: string, newName: string) => Promise<void>;
  moveFiles?: (ids: string[], targetPath: string) => Promise<void>;
  copyFiles?: (ids: string[], targetPath: string) => Promise<void>;
  
  // Heavy file upload tracking (for WebSocket notifications)
  heavyUploads?: Array<{ roomId: string; fileName: string; mediaId: number }>;
  clearHeavyUpload?: (roomId: string) => void;
}

/**
 * Dialog Component Props
 */
export interface DeleteConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onConfirm: () => Promise<void>;
  count: number;
  itemName?: string;
}

export interface MoveCopyDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  mode: 'move' | 'copy';
  count: number;
  onConfirm: (targetPath: string) => Promise<void>;
  currentPath: string;
  selectedFileIds: string[]; // IDs of files/folders being moved
}

export interface NewFolderDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onCreateFolder: (name: string) => Promise<void>;
  isLoading?: boolean;
}

export interface RenameDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  file: MediaFile | null;
  onRename: (file: MediaFile, newName: string) => Promise<void>;
  isLoading?: boolean;
}

export interface UploadDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onUpload: (files: File[]) => Promise<void>;
  currentPath: string;
  isLoading?: boolean;
}

/**
 * Component Props
 */
export interface BreadcrumbItem {
  label: string;
  path: string;
}

export interface BreadcrumbProps {
  items: BreadcrumbItem[];
  onNavigate: (path: string) => void;
}

export interface FileContextMenuProps {
  children: React.ReactNode;
  file: MediaFile;
  onPreview: (file: MediaFile) => void;
  onRename: (file: MediaFile) => void;
  onMove: (file: MediaFile) => void;
  onCopy: (file: MediaFile) => void;
  onDelete: (file: MediaFile) => void;
  onDownload?: (file: MediaFile) => void;
}

export interface FileGridProps {
  files: MediaFile[];
  selectedFiles: string[];
  onSelect: (fileId: string, selected: boolean) => void;
  onFileClick: (file: MediaFile) => void;
  onNavigate: (path: string) => void;
  isLoading?: boolean;
  onPreview: (file: MediaFile) => void;
  onRename: (file: MediaFile) => void;
  onMove: (file: MediaFile) => void;
  onCopy: (file: MediaFile) => void;
  onDelete: (file: MediaFile) => void;
  onDownload?: (file: MediaFile) => void;
}

export interface FileListProps {
  files: MediaFile[];
  selectedFiles: string[];
  onSelect: (fileId: string, selected: boolean) => void;
  onSelectAll: (selected: boolean) => void;
  onFileClick: (file: MediaFile) => void;
  onNavigate: (path: string) => void;
  isLoading?: boolean;
  sortField?: SortField;
  sortOrder?: SortOrder;
  onSort?: (field: SortField) => void;
  onPreview: (file: MediaFile) => void;
  onRename: (file: MediaFile) => void;
  onMove: (file: MediaFile) => void;
  onCopy: (file: MediaFile) => void;
  onDelete: (file: MediaFile) => void;
  onDownload?: (file: MediaFile) => void;
}

export interface PaginationProps {
  pagination: PaginationState;
  onPageChange: (page: number) => void;
}

export interface PreviewModalProps {
  file: MediaFile | null;
  onClose: () => void;
  onDelete?: (file: MediaFile) => void;
  onNext?: () => void;
  onPrev?: () => void;
  hasNext?: boolean;
  hasPrev?: boolean;
}

export interface SidebarFolder {
  id: string;
  name: string;
  path: string;
}

export interface SidebarProps {
  currentPath: string;
  onPathChange: (path: string) => void;
  isOpen?: boolean;
  onToggle?: () => void;
  className?: string;
}

export interface ToolbarProps {
  viewMode: 'grid' | 'list';
  onViewModeChange: (mode: 'grid' | 'list') => void;
  onUpload: () => void;
  onNewFolder: () => void;
  onDelete: () => void;
  onMove: () => void;
  onCopy: () => void;
  onSearchChange: (query: string) => void;
  searchQuery: string;
  selectedCount: number;
  filterOptions: FilterOptions;
  onFilterChange: (options: FilterOptions) => void;
  sortOptions: SortOptions;
  onSortChange: (options: SortOptions) => void;
}
