import toast from 'react-hot-toast';
import { mediaFileService } from '@/features/media/services/media-file.service';
import { UploadDebugger } from '@/features/media/utils/upload-debug';
import { extractMediaItem } from '@/features/media/utils/file-list';
import { UploadStatus } from '@/shared/enums/enums';
import { notification } from '@/shared/utils';

/** A file that the upload dialog already put into temp storage. */
type FileWithTempMeta = File & {
  tempKey?: string;
  isHeavyUploaded?: boolean;
  tempMetadata?: {
    original_name: string;
    extension: string;
    mime_type: string;
    size: number;
  };
};

export interface HeavyUpload {
  roomId: string;
  fileName: string;
  mediaId: number;
}

export interface UploadResult {
  type: 'immediate' | 'heavy' | 'error';
  fileName: string;
  /** Set while the backend is still processing: subscribe to `roomId` for the result. */
  heavy?: HeavyUpload;
}

/**
 * Stores one file in the official bucket: commits the temp upload (uploading to temp first
 * when the dialog did not). Heavy files are processed by a queue job; their status is checked
 * once right away so a job that already finished is not missed while the WebSocket connects.
 */
export async function uploadMediaFile(
  file: File,
  parentPath: string,
  t: (key: string) => string,
): Promise<UploadResult> {
  try {
    const { tempKey, tempMetadata } = file as FileWithTempMeta;
    const metadata =
      tempKey && tempMetadata
        ? { key: tempKey, ...tempMetadata }
        : await mediaFileService.uploadToMinio({ file });

    if (!metadata) return { type: 'error', fileName: file.name };

    const fileName = metadata.original_name;
    const result = await mediaFileService.upload({ file, parent_path: parentPath, ...metadata });
    UploadDebugger.logStoreResponse(result);

    if (result.status !== UploadStatus.PROCESSING || !result.media_id) {
      return { type: 'immediate', fileName };
    }

    notification.info(result.message || 'File đang được xử lý...', { duration: 4000 });

    try {
      const mediaItem = extractMediaItem(await mediaFileService.list({ id: result.media_id }));
      if (mediaItem && mediaItem.upload_status !== UploadStatus.PROCESSING) {
        if (mediaItem.upload_status === UploadStatus.COMPLETED) {
          toast.success(t('uploadSuccess'));
          return { type: 'immediate', fileName };
        }
        toast.error(t('uploadError'));
        return { type: 'error', fileName };
      }
    } catch (error) {
      // Fall back to the WebSocket notification.
      console.warn('[Heavy Upload] Failed to check status, waiting for WebSocket:', error);
    }

    return {
      type: 'heavy',
      fileName,
      heavy: { roomId: result.room_id!, fileName, mediaId: result.media_id },
    };
  } catch (error) {
    console.error(`Failed to upload ${file.name}:`, error);
    const msg = error instanceof Error ? error.message : 'Unknown error';
    toast.error(`${file.name}: ${msg}`);
    return { type: 'error', fileName: file.name };
  }
}
