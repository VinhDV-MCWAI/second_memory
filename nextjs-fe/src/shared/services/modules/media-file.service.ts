/**
 * Media File Service
 * Handles all API calls for media file management
 */

import { apiClient } from '@/shared/api/client';
import { ENDPOINTS } from '@/shared/api';
import { MIME_TYPE_PREFIX, FILE_SIZE_UNITS, FILE_SIZE_MULTIPLIER } from '@/shared/config/constant';
import { API_PATHS } from '@/shared/types/api';
import messages from '../../../../messages/en.json';
import type {
  MediaFile,
  UploadFileParams,
  ListFilesParams,
  RenameFileParams,
  MoveFileParams,
  DeleteFilesParams,
  CreateFolderParams,
  CopyFilesParams,
  MediaApiListResponse,  PresignedUploadResponse,
  InitMultipartUploadResponse,
  GetMultipartUrlResponse,
  MultipartPart,
  HeavyUploadResult,
  UploadResponse, 
} from '@/shared/types/media-file.types';

class MediaFileService {
  private baseUrl = ENDPOINTS.MEDIA.FILES;

  /**
   * Upload file to MinIO via Presigned URL
   * Returns metadata to be used for storing in DB
   */
  async uploadToMinio(params: UploadFileParams & { onProgress?: (percentage: number) => void }): Promise<{ key: string; original_name: string; extension: string; mime_type: string; size: number } | null> {
    try {
      // 0. Client-side Validation
      // Use config or props for validation.

      
      // Let's rely on the passed constraint or a default.
      
      // 1. Get Presigned URL
      const extension = params.file.name.split('.').pop() || '';
      const presignedRes = await apiClient.post<PresignedUploadResponse>(
        `${this.baseUrl}${API_PATHS.PREPARE_UPLOAD}`,
        {
          extension,
          mime_type: params.file.type,
          original_name: params.file.name,
          size: params.file.size
        }
      );
      
      if (!presignedRes.data) {
        throw new Error(messages.errors.failedToGenerateUploadUrl);
      }

      const { upload_url, key, headers } = presignedRes.data;

      // 2. Upload to MinIO with progress tracking
      const uploadResponse = await new Promise<Response>((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        
        // Track upload progress
        if (params.onProgress) {
          xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
              const percentage = Math.round((e.loaded / e.total) * 100);
              params.onProgress?.(percentage);
            }
          });
        }
        
        xhr.addEventListener('load', () => {
          if (xhr.status >= 200 && xhr.status < 300) {
            resolve(new Response(xhr.response, {
              status: xhr.status,
              statusText: xhr.statusText,
            }));
          } else {
            reject(new Error(messages.errors.storageUploadFailed.replace('{status}', xhr.status.toString())));
          }
        });
        
        xhr.addEventListener('error', () => {
          reject(new Error('Upload failed'));
        });
        
        xhr.open('PUT', upload_url);
        
        // Set headers
        Object.entries(headers).forEach(([name, value]) => {
          xhr.setRequestHeader(name, value);
        });
        
        xhr.send(params.file);
      });

      if (!uploadResponse.ok) {
        throw new Error(messages.errors.storageUploadFailed.replace('{status}', uploadResponse.status.toString()));
      }

      // 3. Return metadata
      return {
        key,
        original_name: params.file.name,
        extension,
        mime_type: params.file.type,
        size: params.file.size
      };

    } catch (error) {
      console.error(messages.errors.uploadToMinioFailed, error);
      throw error;
    }
  }

  /**
   * Upload file to server (Updated to support Store from Temp)
   * Returns unified UploadResponse for both light and heavy files
   */
  async upload(params: UploadFileParams & { key?: string, original_name?: string, extension?: string, mime_type?: string, size?: number }): Promise<UploadResponse> {
    const formData = new FormData();
    
    // If key is present, we are storing from temp
    if (params.key) {
        formData.append('key', params.key);
        if (params.original_name) formData.append('original_name', params.original_name);
        if (params.extension) formData.append('extension', params.extension);
        if (params.mime_type) formData.append('mime_type', params.mime_type);
        if (params.size) formData.append('size', params.size.toString());
    } else {
        // Fallback: Direct upload
        formData.append('file', params.file);
    }
    
    if (params.parent_path) {
      formData.append('parent_path', params.parent_path);
    }
    
    if (params.workspace_id !== undefined) {
      formData.append('workspace_id', params.workspace_id.toString());
    }

    const response = await apiClient.post<UploadResponse>(
      `${this.baseUrl}${API_PATHS.STORE}`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );
    return response.data;
  }

  /**
   * Get list of media files
   */
  async list(params?: ListFilesParams, config?: { signal?: AbortSignal }): Promise<MediaApiListResponse> {
    const response = await apiClient.get<MediaApiListResponse>(
      `${this.baseUrl}${API_PATHS.LIST}`,
      { params, signal: config?.signal }
    );
    return response.data;
  }

  /**
   * Get single media file by ID
   */
  async get(id: number): Promise<MediaFile> {
    const response = await apiClient.get<MediaFile>(`${this.baseUrl}/${id}`);
    return response.data;
  }

  /**
   * Get file view URL
   */
  getViewUrl(id: number): string {
    return `${this.baseUrl}/${id}${API_PATHS.VIEW}`;
  }

  /**
   * Get file download URL
   */
  getDownloadUrl(id: number): string {
    return `${this.baseUrl}/${id}${API_PATHS.DOWNLOAD}`;
  }

  /**
   * Download file
   */
  async download(id: number, filename?: string): Promise<void> {
    const url = this.getDownloadUrl(id);
    const response = await fetch(url);
    const blob = await response.blob();
    
    // Create download link
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename || `file-${id}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(downloadUrl);
  }

  /**
   * Rename file or folder
   */
  async rename(id: number, params: RenameFileParams): Promise<number> {
    const response = await apiClient.put<number>(
      `${this.baseUrl}${API_PATHS.UPDATE}/${id}`,
      { name: params.name }
    );
    return response.data;
  }

  /**
   * Move file to different folder
   */
  async move(id: number, params: MoveFileParams): Promise<number> {
    const response = await apiClient.put<number>(
      `${this.baseUrl}${API_PATHS.UPDATE}/${id}`,
      { new_parent_path: params.new_parent_path }
    );
    return response.data;
  }

  /**
   * Delete files
   */
  async delete(params: DeleteFilesParams): Promise<void> {
    // For batch delete, use first ID in route and send all IDs in body
    const firstId = params.ids[0];
    await apiClient.delete(
      `${this.baseUrl}${API_PATHS.DELETE}/${firstId}`,
      { data: { ids: params.ids } }
    );
  }

  /**
   * Delete single file
   */
  async deleteSingle(id: number): Promise<void> {
    await apiClient.delete(`${this.baseUrl}${API_PATHS.DELETE}/${id}`);
  }

  /**
   * Get file type icon based on MIME type
   */
  getFileTypeIcon(mimeType: string): string {
    if (mimeType.startsWith(MIME_TYPE_PREFIX.IMAGE)) return '🖼️';
    if (mimeType.startsWith(MIME_TYPE_PREFIX.VIDEO)) return '🎥';
    if (mimeType.includes('pdf')) return '📄';
    if (mimeType.includes('word') || mimeType.includes('document')) return '📝';
    if (mimeType.includes('sheet') || mimeType.includes('excel')) return '📊';
    return '📁';
  }

  /**
   * Format file size to human readable format
   */
  formatFileSize(bytes: number): string {
    let size = bytes;
    let unitIndex = 0;
    
    while (size >= FILE_SIZE_MULTIPLIER && unitIndex < FILE_SIZE_UNITS.length - 1) {
      size /= FILE_SIZE_MULTIPLIER;
      unitIndex++;
    }
    
    return `${size.toFixed(2)} ${FILE_SIZE_UNITS[unitIndex]}`;
  }

  /**
   * Create folder
   */
  async createFolder(params: CreateFolderParams): Promise<number> {
    const response = await apiClient.post<number>(
      `${this.baseUrl}${API_PATHS.STORE}`,
      {
        name: params.name,
        parent_path: params.parent_path,
        workspace_id: params.workspace_id,
      }
    );
    return response.data;
  }

  /**
   * List folders
   */
  async listFolders(params?: ListFilesParams): Promise<MediaApiListResponse> {
    const response = await apiClient.get<MediaApiListResponse>(
      `${this.baseUrl}${API_PATHS.LIST}`,
      { params: { ...params, is_file: 0 } }
    );
    return response.data;
  }

  /**
   * Copy files to different folder
   * Note: Copy functionality not implemented in backend yet
   */
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  async copy(_params: CopyFilesParams): Promise<{ copied_count: number; copied_ids: number[] }> {
    // This is a placeholder for future implementation
    throw new Error(messages.errors.copyNotImplemented);
  }

  /**
   * Initialize Multipart Upload
   */
  async initMultipartUpload(params: { extension: string; size: number; mime_type: string; original_name: string }): Promise<InitMultipartUploadResponse> {
    const response = await apiClient.post<InitMultipartUploadResponse>(
      `${this.baseUrl}${API_PATHS.INIT_MULTIPART_UPLOAD}`,
      params
    );
    return response.data;
  }

  /**
   * Get Multipart Presigned URL
   */
  async getMultipartPresignedUrl(params: { key: string; upload_id: string; part_number: number; size: number }): Promise<GetMultipartUrlResponse> {
    const response = await apiClient.post<GetMultipartUrlResponse>(
      `${this.baseUrl}${API_PATHS.GET_MULTIPART_URL}`,
      params
    );
    return response.data;
  }

  /**
   * Complete Multipart Upload
   */
  async completeMultipartUpload(params: { 
    key: string; 
    upload_id: string; 
    parts: MultipartPart[]; 
    original_name: string;
    extension: string;
    size: number;
    mime_type: string;
    workspace_id?: number;
    parent_path?: string;
  }): Promise<HeavyUploadResult> {
    const response = await apiClient.post<HeavyUploadResult>(
      `${this.baseUrl}${API_PATHS.COMPLETE_MULTIPART_UPLOAD}`,
      params
    );
    return response.data;
  }
}

export const mediaFileService = new MediaFileService();
