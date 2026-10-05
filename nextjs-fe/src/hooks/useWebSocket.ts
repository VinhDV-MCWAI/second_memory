import { useEffect, useState, useRef, useCallback } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Ensure Pusher is available globally for Echo
// eslint-disable-next-line @typescript-eslint/no-explicit-any
(globalThis as any).Pusher = Pusher;

interface WebSocketConfig {
  token?: string;
  roomId?: string; // Used to derive channel name: private-upload.status.{roomId}
}

interface WebSocketMessage {
  type: string;
  roomId?: string;
  [key: string]: unknown;
}

export const useWebSocket = ({
  token,
  roomId,
}: WebSocketConfig) => {
  const [isConnected, setIsConnected] = useState(false);
  const [lastMessage, setLastMessage] = useState<WebSocketMessage | null>(null);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const echoRef = useRef<Echo<any> | null>(null);

  useEffect(() => {
    // Only initialize Echo if roomId is provided
    // This prevents unnecessary WebSocket connections
    if (!roomId) {
      console.log('[useWebSocket] No roomId provided, skipping initialization');
      return;
    }

    console.log('[useWebSocket] Initializing Echo for roomId:', roomId);
    
    // Cookie-based auth: Initialize Echo
    // The HttpOnly cookie will be sent automatically

    // Configure Echo with Reverb
    // Reverb uses Pusher protocol
    const echoConfig = {
      broadcaster: 'reverb' as const,
      key: process.env.NEXT_PUBLIC_REVERB_APP_KEY || 'my-app-key',
      wsHost: process.env.NEXT_PUBLIC_REVERB_HOST || 'localhost',
      wsPort: process.env.NEXT_PUBLIC_REVERB_PORT ? parseInt(process.env.NEXT_PUBLIC_REVERB_PORT) : 81,
      wssPort: process.env.NEXT_PUBLIC_REVERB_PORT ? parseInt(process.env.NEXT_PUBLIC_REVERB_PORT) : 81,
      wsPath: process.env.NEXT_PUBLIC_REVERB_PATH || '/app',
      forceTLS: (process.env.NEXT_PUBLIC_REVERB_SCHEME || 'http') === 'https',
      enabledTransports: ['ws', 'wss'],
      // Cookie will be sent automatically by browser (path=/api/admin, httpOnly)
      authEndpoint: `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost:81/api'}/admin/broadcasting/auth`,
      auth: {
        headers: token ? {
          Authorization: `Bearer ${token}`,
        } : {},
      },
      authorizer: (channel: any) => {
        return {
          authorize: (socketId: string, callback: (error: Error | null, data: any) => void) => {
            console.log('[useWebSocket] Authorizing channel:', channel.name, 'socketId:', socketId);
            // Use fetch with credentials to send cookies
            fetch(`${process.env.NEXT_PUBLIC_API_URL || 'http://localhost:81/api'}/admin/broadcasting/auth`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
              },
              credentials: 'include', // CRITICAL: Send cookies
              body: JSON.stringify({
                socket_id: socketId,
                channel_name: channel.name,
              }),
            })
              .then(response => {
                console.log('[useWebSocket] Auth response status:', response.status);
                if (!response.ok) {
                  throw new Error(`Auth failed: ${response.status} ${response.statusText}`);
                }
                return response.json();
              })
              .then(data => {
                console.log('[useWebSocket] Auth successful:', data);
                callback(null, data);
              })
              .catch(error => {
                console.error('[useWebSocket] Auth error:', error);
                callback(error, null);
              });
          }
        };
      },
    };

    console.log('[useWebSocket] Echo config:', echoConfig);

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const echo = new Echo(echoConfig as any);

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (echo.connector as any).pusher?.connection.bind('connected', () => {
      console.log('[useWebSocket] Pusher connected!');
      setIsConnected(true);
    });

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (echo.connector as any).pusher?.connection.bind('disconnected', () => {
      console.log('[useWebSocket] Pusher disconnected');
      setIsConnected(false);
    });

    echoRef.current = echo;

    return () => {
      console.log('[useWebSocket] Cleaning up Echo connection');
      echo.disconnect();
    };
  }, [token, roomId]); // Include roomId to reconnect when it changes

  // Subscribe to Room Channel
  useEffect(() => {
    if (!echoRef.current || !roomId || !isConnected) {
      console.log('[useWebSocket] Skipping channel subscription:', { 
        hasEcho: !!echoRef.current, 
        roomId, 
        isConnected 
      });
      return;
    }

    // Correct channel name mapping based on backend: `upload.status.{roomId}`
    // `private-` prefix is added automatically by `.private()`
    const channelName = `upload.status.${roomId}`;
    console.log('[useWebSocket] Subscribing to private channel:', channelName);
    
    const channel = echoRef.current.private(channelName);

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    channel.listen('.upload.status.updated', (event: any) => {
      console.log('[useWebSocket] Received event .upload.status.updated:', event);
      // Map event data to legacy WebSocketMessage format for compatibility
      setLastMessage({
        type: 'UPLOAD_STATUS', // Synthetic type
        roomId: event.roomId,
        status: event.status,
        message: event.message,
        fileId: event.fileId,
        url: event.url,
      });
    });

    return () => {
      console.log('[useWebSocket] Leaving channel:', channelName);
      echoRef.current?.leave(channelName);
    };
  }, [roomId, isConnected]);

  // Method to leave/close room and cleanup connection
  const leaveRoom = useCallback(() => {
    if (echoRef.current && roomId) {
      const channelName = `upload.status.${roomId}`;
      echoRef.current.leave(channelName);
      // Disconnect Echo entirely to free resources
      echoRef.current.disconnect();
      echoRef.current = null;
      setIsConnected(false);
    }
  }, [roomId]);

  // Deprecated compatibility methods
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const sendMessage = useCallback((_data: unknown) => {
     console.warn('sendMessage is not supported with Laravel Echo in this implementation');
  }, []);

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const joinRoom = useCallback((_id: string) => {
     // Managed via prop `roomId` now
     console.warn('joinRoom is handled via props in this implementation');
  }, []);

  return { isConnected, lastMessage, sendMessage, joinRoom, leaveRoom };
};
