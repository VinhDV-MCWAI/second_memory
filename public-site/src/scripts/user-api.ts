// Browser side of end-user accounts (ADR-0020). Same origin as the API, session cookie + XSRF
// header as the admin app does it. Visitors who never signed in make no API call: the marker
// cookie below (no secret in it) tells the pages that a session may exist.
import { ROUTES } from '@/lib/routes';
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

/** `keepalive`: the request outlives the page (a reading position sent while leaving it) */
export async function api<T>(
  method: string,
  path: string,
  body?: unknown,
  { keepalive = false } = {},
): Promise<T> {
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
    keepalive,
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

// The ☆ 🔖 buttons and the reading position of one page share one request; a page reached through
// the reading page's router (ADR-0024) has another address, so it asks again
let saved: { page: string; items: Promise<SavedItem[]> } | null = null;

export function savedItems(): Promise<SavedItem[]> {
  if (saved?.page !== location.href) {
    const items = api<{ data: SavedItem[] }>('GET', 'user/saved/list').then(({ data }) => data);
    saved = { page: location.href, items };
    items.catch(() => (saved = null));
  }
  return saved.items;
}

/** After saving or removing something, the next savedItems() asks the API again */
export function forgetSaved(): void {
  saved = null;
}

/** Where to go after signing in: only pages of this site, never the admin app. */
export function nextPage(): string {
  const next = new URLSearchParams(location.search).get('next');
  // "//host" and "/\\host" would leave the site
  const local = next !== null && next.startsWith('/') && !/^\/[/\\]/.test(next);
  return local && !next.startsWith('/admin') ? next : ROUTES.ME;
}

export function loginUrl(): string {
  return `${ROUTES.LOGIN}?next=${encodeURIComponent(location.pathname)}`;
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
