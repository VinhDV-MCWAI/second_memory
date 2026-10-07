import { setupServer } from 'msw/node';
import { API_BASE_URL } from '@/shared/api/endpoints';

// Requests not matched by a test's handlers fail loudly instead of hitting the network.
export const server = setupServer();

export const apiUrl = (path: string) => `${API_BASE_URL}${path}`;

/** Backend envelope: `{ data, error: { status, code, messages } }` */
export const envelope = <T>(data: T, code = 200) => ({
  data,
  error: { status: false, code, messages: null },
});

export const errorEnvelope = (code: number, messages: unknown) => ({
  data: null,
  error: { status: true, code, messages },
});
