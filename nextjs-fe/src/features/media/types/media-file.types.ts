/**
 * Media File Types
 * Updated to match MediaMgmt backend model
 */

export interface MediaFile {
  id: number;
  workspace_id: number | null;
  is_file: boolean;
  virtual_path: string;
  storage_path: string | null;
  original_name: string;
  extension: string | null;
  mime_type: string | null;
  size: number | null;
  minio_bucket: string | null;
  minio_object_key: string | null;
  minio_etag: string | null;
  url: string | null;
  width: number | null;
  height: number | null;
  duration: number | null;
  metadata: Record<string, unknown> | null;
  is_delete: boolean;
  created_by: number | null;
  updated_by: number | null;
  created_at: string;
  updated_at: string;
  upload_status?: number;
}

export interface UploadFileParams {
  file: File;
  parent_path?: string;
  workspace_id?: number;
  is_public?: boolean;
}

export interface ListFilesParams {
  parent_path?: string;
  is_file?: boolean;
  mime_type?: string;
  search?: string;
  order_by?: 'created_at' | 'original_name' | 'size';
  order_direction?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
  file_type?: string;
  [key: string]: string | number | boolean | undefined;
}

export interface RenameFileParams {
  name: string;
}

export interface MoveFileParams {
  new_parent_path: string;
}

export interface DeleteFilesParams {
  ids: number[];
}

export interface CreateFolderParams {
  name: string;
  parent_path?: string;
  workspace_id?: number;
}

export interface CopyFilesParams {
  ids: number[];
  target_folder_path: string;
}

export interface UploadFileResponse {
  id: number;
  original_name: string;
  size: number;
  virtual_path: string;
}

export type FileType = 'images' | 'videos' | 'documents' | 'all';

export interface FileTypeFilter {
  label: string;
  value: FileType;
  icon?: string;
}

/**
 * Media Upload Component Types
 */

export interface FileUploadProps {
  onUploadSuccess?: (file: UploadFileResponse | UploadResponse | number) => void;
  onUploadError?: (error: Error) => void;
  accept?: string;
  maxSize?: number; // in bytes
  isPublic?: boolean;
}

/**
 * Upload Component Types
 */

export interface SimpleFileUploadProps {
  value?: string;
  onChange: (url: string) => void;
  accept?: string;
  maxSize?: number; // in MB
  className?: string;
  disabled?: boolean;
}

export interface UploadError {
  message: string;
  code?: string;
}

export interface MediaApiListResponse {
  data: MediaFile[];
  error?: {
    status: boolean;
    code: number;
    messages: unknown;
  };
  // API response might not always have pagination, but UI code expects it
  current_page?: number;
  last_page?: number;
  total?: number;
  per_page?: number;
}

export interface PresignedUploadResponse {
  upload_url: string;
  method: string;
  key: string;
  uuid: string;
  headers: Record<string, string>;
  expires_in: number;
}

export interface InitMultipartUploadResponse {
  upload_id: string;
  key: string;
  part_size: number;
  parts_count: number;
}

export interface GetMultipartUrlResponse {
  url: string;
  part_number: number;
  expires_in: number;
}

export interface MultipartPart {
  part_number: number;
  etag: string;
}

export interface UploadProgress {
  loaded: number;
  total: number;
  percentage: number;
}

export interface Part {
  partNumber: number;
  start: number;
  end: number;
  blob: Blob;
  url?: string;
  etag?: string;
  attempts: number;
}

export interface HeavyUploadResult {
  original_name: string;
  extension: string;
  mime_type: string;
  size: number;
  key: string;
  media_id?: number;
  room_id?: string;
  status?: number;
  message?: string;
}

/**
 * Unified upload response for both light and heavy files
 */
export interface UploadResponse {
  media_id: number;
  room_id: string | null;
  status: number; // 1 = PROCESSING, 2 = COMPLETED, 3 = FAILED
  message: string;
  url?: string;
  upload_status?: number;
}
