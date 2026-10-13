import Echo from 'laravel-echo';
import Pusher, { type ChannelAuthorizationCallback } from 'pusher-js';
import { apiClient, API_BASE_URL, API_ENDPOINTS } from '@/shared/api';

/** `{ auth }` signed by Laravel for one private channel (pusher-js does not export the type). */
type ChannelAuthorizationData = NonNullable<Parameters<ChannelAuthorizationCallback>[1]>;

/**
 * One WebSocket connection to Reverb per tab (ADR-0014). It goes through nginx on the API's host
 * (`ws://localhost:81/app/<key>`); private channels are authorized by Laravel with the session cookie.
 */
let echo: Echo<'reverb'> | null = null;

/** The connection, or null when live notifications are not configured (no Reverb key). */
export function getEcho(): Echo<'reverb'> | null {
  const key = process.env.NEXT_PUBLIC_REVERB_APP_KEY;
  if (echo || !key || typeof window === 'undefined') return echo;

  // API_BASE_URL is absolute in dev (http://localhost:81/api) and `/api` in production images
  const api = new URL(API_BASE_URL, window.location.origin);
  const secure = api.protocol === 'https:';
  const port = Number(api.port) || (secure ? 443 : 80);

  echo = new Echo({
    broadcaster: 'reverb',
    Pusher,
    key,
    wsHost: api.hostname,
    wsPort: port,
    wssPort: port,
    forceTLS: secure,
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel: { name: string }) => ({
      authorize: (socketId: string, callback: ChannelAuthorizationCallback) => {
        // Raw axios: the auth endpoint answers `{ auth }` without the API envelope
        apiClient
          .getAxiosInstance()
          .post<ChannelAuthorizationData>(API_ENDPOINTS.SYSTEM.BROADCASTING_AUTH, {
            socket_id: socketId,
            channel_name: channel.name,
          })
          .then(({ data }) => callback(null, data))
          .catch((error: unknown) =>
            callback(error instanceof Error ? error : new Error(String(error)), null),
          );
      },
    }),
  });

  return echo;
}

/** Closes the connection (sign-out). */
export function disconnectEcho(): void {
  echo?.disconnect();
  echo = null;
}
