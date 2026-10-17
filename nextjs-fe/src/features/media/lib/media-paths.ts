import { ADMIN_ROUTES } from '@/shared/config';

/** Virtual paths of the file manager (ADR-0014): `/` is the root, `/Docs/report.pdf` a file in Docs. */
export const ROOT = '/';

export function joinPath(folder: string, name: string): string {
  return `${folder === ROOT ? '' : folder}/${name}`;
}

/** The Files screen opened on a folder. */
export function filesHref(folder: string): string {
  return folder === ROOT
    ? ADMIN_ROUTES.FILES
    : `${ADMIN_ROUTES.FILES}?folder=${encodeURIComponent(folder)}`;
}

export function parentOf(path: string): string {
  const parent = path.slice(0, path.lastIndexOf('/'));
  return parent === '' ? ROOT : parent;
}

/** Breadcrumb of a folder: `/A/B` → [{ name: 'A', path: '/A' }, { name: 'B', path: '/A/B' }]. */
export function crumbsOf(folder: string): { name: string; path: string }[] {
  const names = folder.split('/').filter(Boolean);
  return names.map((name, index) => ({ name, path: `/${names.slice(0, index + 1).join('/')}` }));
}

const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

export function formatBytes(bytes: number | null | undefined): string {
  if (bytes === null || bytes === undefined) return '';
  let value = bytes;
  let unit = 0;
  while (value >= 1024 && unit < UNITS.length - 1) {
    value /= 1024;
    unit++;
  }
  return `${unit === 0 ? value : value.toFixed(value < 10 ? 1 : 0)} ${UNITS[unit]}`;
}

/** A length in seconds as `m:ss`, or `h:mm:ss` from an hour on. */
export function formatDuration(seconds: number): string {
  const pad = (value: number) => String(value).padStart(2, '0');
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const rest = seconds % 60;
  return hours > 0 ? `${hours}:${pad(minutes)}:${pad(rest)}` : `${minutes}:${pad(rest)}`;
}

/**
 * Size of a file for the Files screen: bytes, plus width × height of an image or video (MEDIA-10)
 * and the length of a video or audio (MEDIA-11).
 */
export function formatFileSize(item: {
  size?: number | null;
  width?: number | null;
  height?: number | null;
  duration?: number | null;
}): string {
  const parts = [formatBytes(item.size)];
  if (item.width && item.height) parts.push(`${item.width} × ${item.height}`);
  if (item.duration !== null && item.duration !== undefined)
    parts.push(formatDuration(item.duration));
  return parts.join(' · ');
}
