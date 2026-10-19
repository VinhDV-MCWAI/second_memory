// Browser side of end-user accounts (ADR-0020). Same origin as the API, session cookie + XSRF
// header as the admin app does it. Visitors who never signed in make no API call: the marker
// cookie below (no secret in it) tells the pages that a session may exist.
import type { components } from '@/types/openapi';

export type SavedItem = components['schemas']['SavedItemResource'];
export type SavedKind = components['schemas']['SavedKind'];
export interface CurrentUser {
  username: string;
  display_name: string;
}

const MARKER = 'sm_user';
const MARKER_DAYS = 30;
const DAY_SECONDS = 86_400;
const HTTP_UNAUTHORIZED = 401;
const BASE = '/skills';

export class ApiError extends Error {
  constructor(
    readonly status: number,
    readonly messages: string | Record<string, string[]> | null,
  ) {
    super(typeof messages === 'string' ? messages : `HTTP ${status}`);
  }

  /** One line per message, for a form's error box. */
  lines(): string[] {
    if (this.messages === null) return [this.message];
    return typeof this.messages === 'string'
      ? [this.messages]
      : Object.values(this.messages).flat();
  }
}

export function maybeSignedIn(): boolean {
  return document.cookie.split('; ').some((part) => part === `${MARKER}=1`);
}

function setMarker(on: boolean): void {
  const maxAge = on ? MARKER_DAYS * DAY_SECONDS : 0;
  document.cookie = `${MARKER}=1; path=/; max-age=${maxAge}; samesite=lax`;
}

function xsrfToken(): string | null {
  const part = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));
  return part ? decodeURIComponent(part.slice('XSRF-TOKEN='.length)) : null;
}

export async function api<T>(method: string, path: string, body?: unknown): Promise<T> {
  if (method !== 'GET' && xsrfToken() === null) {
    await fetch('/api/sanctum/csrf-cookie', { credentials: 'same-origin' });
  }
  const response = await fetch(`/api/${path}`, {
    method,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
      ...(method === 'GET' ? {} : { 'X-XSRF-TOKEN': xsrfToken() ?? '' }),
    },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  const json = (await response.json().catch(() => null)) as {
    data: T;
    error: { messages: string | Record<string, string[]> | null };
  } | null;
  if (!response.ok) {
    if (response.status === HTTP_UNAUTHORIZED) setMarker(false);
    throw new ApiError(response.status, json?.error.messages ?? null);
  }
  return (json as { data: T }).data;
}

export async function currentUser(): Promise<CurrentUser | null> {
  if (!maybeSignedIn()) return null;
  try {
    return await api<CurrentUser>('GET', 'user/me');
  } catch {
    return null;
  }
}

export async function signIn(path: 'user/login' | 'user/register', body: object): Promise<void> {
  await api('POST', path, body);
  setMarker(true);
}

export async function signOut(): Promise<void> {
  await api('POST', 'user/logout').catch(() => undefined);
  setMarker(false);
}

export async function savedItems(): Promise<SavedItem[]> {
  return (await api<{ data: SavedItem[] }>('GET', 'user/saved/list')).data;
}

/** Where to go after signing in: only pages of this site. */
export function nextPage(): string {
  const next = new URLSearchParams(location.search).get('next');
  return next !== null && next.startsWith(`${BASE}/`) && !next.startsWith('//')
    ? next
    : `${BASE}/me`;
}

export function loginUrl(): string {
  return `${BASE}/account/login?next=${encodeURIComponent(location.pathname)}`;
}

/** Shows the API's messages under a form. */
export function showErrors(box: HTMLElement, error: unknown): void {
  box.replaceChildren(
    ...(error instanceof ApiError ? error.lines() : ['Something went wrong, try again.']).map(
      (line) => Object.assign(document.createElement('p'), { textContent: line }),
    ),
  );
  box.hidden = false;
}
