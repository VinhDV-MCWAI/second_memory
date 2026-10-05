'use client';

import { useEffect, useRef, useCallback } from 'react';
import { useWebSocket } from '@/hooks/useWebSocket';
import { notification } from '@/shared/utils';

interface HeavyUploadNotificationProps {
  roomId: string;
  fileName: string;
  mediaId: number;
  onComplete: () => void;
}

/**
 * Component to handle WebSocket notification for heavy file upload
 * Automatically connects to room, listens for status updates, and disconnects
 * Uses cookie-based auth (HttpOnly cookie sent automatically)
 * 
 * Improvements:
 * - Proper cleanup to prevent memory leaks
 * - Timeout fallback if no response after 15 minutes
 * - Prevent duplicate notifications with better state management
 */
export function HeavyUploadNotification({ roomId, fileName, mediaId, onComplete }: HeavyUploadNotificationProps) {
  console.log('[HeavyUploadNotification] Component mounted:', { roomId, fileName, mediaId });
  
  // Pass roomId only - token will be sent via HttpOnly cookie automatically
  const { isConnected, lastMessage, leaveRoom } = useWebSocket({ roomId });
  const hasNotified = useRef(false);
  const hasCheckedStatus = useRef(false); // Track if we've already checked status
  const timeoutRef = useRef<NodeJS.Timeout | null>(null);

  // Memoized complete handler to prevent re-creating
  const handleComplete = useCallback((status: 'success' | 'error' | 'timeout', message: string) => {
    if (hasNotified.current) return;
    hasNotified.current = true;

    // Clear timeout if exists
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
      timeoutRef.current = null;
    }

    // Leave WebSocket room to free resources
    if (leaveRoom) {
      leaveRoom();
    }

    // Show notification
    if (status === 'success') {
      notification.success(`✓ ${fileName}: ${message}`);
    } else if (status === 'error') {
      notification.error(`✗ ${fileName}: ${message}`);
    } else {
      // notification.warning(`⚠ ${fileName}: ${message}`);
    }

    // Cleanup
    onComplete();
  }, [fileName, onComplete, leaveRoom]);

  // Listen for WebSocket messages
  useEffect(() => {
    console.log('[HeavyUploadNotification] isConnected changed:', isConnected);
    if (isConnected) {
      console.log('[HeavyUploadNotification] WebSocket connected for room:', roomId);
    }
  }, [isConnected, roomId]);

  useEffect(() => {
    if (!lastMessage) return;

    console.log('[HeavyUploadNotification] Received WebSocket message:', lastMessage);
    
    // Check if this is an upload status message for our room
    if (lastMessage.type === 'UPLOAD_STATUS' && lastMessage.roomId === roomId) {
      const { status, message } = lastMessage;
      console.log('[HeavyUploadNotification] Processing upload status:', { status, message });

      if (status === 2) {
        // COMPLETED
        handleComplete('success', message as string);
      } else if (status === 3) {
        // FAILED
        handleComplete('error', message as string);
      }
    }
  }, [lastMessage, roomId, handleComplete]);

  // Timeout fallback - if no response after 15 minutes, show warning
  useEffect(() => {
    timeoutRef.current = setTimeout(() => {
      handleComplete('timeout', 'Upload taking longer than expected. Check status later.');
    }, 15 * 60 * 1000); // 15 minutes

    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, [handleComplete]);

  // Check status immediately after WSS connected successfully
  // Only check ONCE when first connected to avoid redundant API calls
  useEffect(() => {
    if (!isConnected || !mediaId || hasCheckedStatus.current) return;

    // Mark as checked to prevent duplicate calls
    hasCheckedStatus.current = true;

    console.log('[HeavyUploadNotification] Checking upload status once after WSS connected');

    // Import mediaFileService dynamically to avoid circular dependency
    import('@/shared/services/modules/media-file.service').then(({ mediaFileService }) => {
      // Check current upload status
      mediaFileService.list({ id: mediaId })
        .then((listResponse) => {
          // Extract media item from response
          let mediaItem: { upload_status?: number } | undefined;
          if (Array.isArray(listResponse)) {
            mediaItem = listResponse[0] as { upload_status?: number };
          } else if (listResponse && typeof listResponse === 'object' && 'data' in listResponse) {
            const data = (listResponse as { data: unknown }).data;
            if (Array.isArray(data)) {
              mediaItem = data[0] as { upload_status?: number };
            }
          }

          if (mediaItem && mediaItem.upload_status !== undefined) {
            // UploadStatus: 1=PROCESSING, 2=COMPLETED, 3=FAILED
            if (mediaItem.upload_status === 2) {
              // Already completed - show success immediately
              handleComplete('success', 'Upload completed successfully.');
            } else if (mediaItem.upload_status === 3) {
              // Already failed - show error immediately
              handleComplete('error', 'Upload failed.');
            }
            // If status = 1 (PROCESSING), wait for WebSocket notification
          }
        })
        .catch((error) => {
          console.warn('Failed to check upload status after WSS connected:', error);
          // Continue waiting for WebSocket notification if check fails
        });
    });
  }, [isConnected, mediaId]); // Remove handleComplete from dependencies

  return null; // This is a headless component
}
