/** Virtual paths of the file manager (ADR-0014): `/` is the root, `/Docs/report.pdf` a file in Docs. */
export const ROOT = '/';

export function joinPath(folder: string, name: string): string {
  return `${folder === ROOT ? '' : folder}/${name}`;
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
