/**
 * Upload Debug Utilities
 * For debugging and logging upload API responses
 */

import type { UploadResponse } from '@/shared/types/media-file.types';

export class UploadDebugger {
  /**
   * Log store API response with detailed information
   */
  static logStoreResponse(response: UploadResponse) {
    const logData = {
      timestamp: new Date().toISOString(),
      response,
      roomId: response.room_id,
      mediaId: response.media_id,
      status: response.status,
      statusName: this.getStatusName(response.status),
      message: response.message,
      isHeavyUpload: response.status === 1, // 1 = PROCESSING (heavy upload)
      isCompleted: response.status === 2, // 2 = COMPLETED (light upload)
      isFailed: response.status === 3, // 3 = FAILED
    };

    console.group('📤 [Store API Response]');
    console.log('Full Response:', logData.response);
    console.log('Room ID:', logData.roomId);
    console.log('Media ID:', logData.mediaId);
    console.log('Status:', `${logData.status} (${logData.statusName})`);
    console.log('Message:', logData.message);
    console.log('Is Heavy Upload:', logData.isHeavyUpload);
    console.log('Is Completed:', logData.isCompleted);
    console.table(logData);
    console.groupEnd();

    return logData;
  }

  /**
   * Get human readable status name
   */
  private static getStatusName(status: number): string {
    const statusMap: Record<number, string> = {
      1: 'PROCESSING (Heavy Upload)',
      2: 'COMPLETED (Light Upload)',
      3: 'FAILED',
    };
    return statusMap[status] || 'UNKNOWN';
  }

  /**
   * Log comparison between light and heavy uploads
   */
  static logUploadComparison(lightUpload: UploadResponse, heavyUpload: UploadResponse) {
    console.group('🔄 [Upload Types Comparison]');
    console.table({
      'Light Upload': {
        mediaId: lightUpload.media_id,
        roomId: lightUpload.room_id,
        status: this.getStatusName(lightUpload.status),
        message: lightUpload.message,
      },
      'Heavy Upload': {
        mediaId: heavyUpload.media_id,
        roomId: heavyUpload.room_id,
        status: this.getStatusName(heavyUpload.status),
        message: heavyUpload.message,
      },
    });
    console.groupEnd();
  }
}

/**
 * Usage in component or hook:
 * 
 * import { UploadDebugger } from '@/shared/utils/upload-debug';
 * 
 * const result = await mediaFileService.upload({ ... });
 * UploadDebugger.logStoreResponse(result);
 */
