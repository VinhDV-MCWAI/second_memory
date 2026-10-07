import type {
  FilterOptions,
  MediaFile,
  SortOptions,
} from '@/features/media/types/file-manager.types';
import {
  FILE_MANAGER_SORT_FIELDS,
  FILE_TYPE,
  FILTER_TYPE,
  MIME_TYPE_PREFIX,
  SORT_ORDER,
} from '@/shared/config/constant';

/** Rows of a media list response: either the array itself or `{ data: [...] }`. */
export function extractList(response: unknown): unknown[] {
  if (Array.isArray(response)) return response;
  if (
    response &&
    typeof response === 'object' &&
    'data' in response &&
    Array.isArray((response as { data: unknown[] }).data)
  ) {
    return (response as { data: unknown[] }).data;
  }
  return [];
}

/** First media item of a list response (used for status checks). */
export function extractMediaItem(listResponse: unknown): MediaFile | undefined {
  return extractList(listResponse)[0] as MediaFile | undefined;
}

/** Maps an API media row to the file manager's `MediaFile`. */
export function toMediaFile(fileData: unknown): MediaFile {
  const file = fileData as Record<string, unknown>;
  const mimeType = (file.mime_type as string | null | undefined) || '';
  return {
    id: String(file.id),
    drive_id: String(file.id),
    name: file.original_name as string,
    mime_type: mimeType,
    url: (file.view_url as string) || (file.url as string) || '',
    thumbnail_url: mimeType?.startsWith(MIME_TYPE_PREFIX.IMAGE)
      ? (file.view_url as string) || (file.url as string)
      : undefined,
    folder_path: (file.folder_path as string) || '/',
    size: file.size as number,
    owner_id: String(file.workspace_id || 0),
    created_at: file.created_at as string,
    updated_at: file.updated_at as string,
    type: !file.is_file ? FILE_TYPE.FOLDER : FILE_TYPE.FILE,
  };
}

function matchesType(file: MediaFile, type: FilterOptions['type']): boolean {
  if (type === FILTER_TYPE.FOLDERS) return file.type === FILE_TYPE.FOLDER;
  if (type === FILTER_TYPE.IMAGES) return !!file.mime_type?.startsWith(MIME_TYPE_PREFIX.IMAGE);
  if (type === FILTER_TYPE.VIDEOS) return !!file.mime_type?.startsWith(MIME_TYPE_PREFIX.VIDEO);
  if (type === FILTER_TYPE.DOCUMENTS) {
    return (
      file.type === FILE_TYPE.FILE &&
      !!file.mime_type &&
      !file.mime_type.startsWith(MIME_TYPE_PREFIX.IMAGE) &&
      !file.mime_type.startsWith(MIME_TYPE_PREFIX.VIDEO)
    );
  }
  return true;
}

function compareFiles(a: MediaFile, b: MediaFile, sort: SortOptions): number {
  const multiplier = sort.order === SORT_ORDER.ASC ? 1 : -1;

  switch (sort.field) {
    case FILE_MANAGER_SORT_FIELDS.NAME:
      return a.name.localeCompare(b.name) * multiplier;
    case FILE_MANAGER_SORT_FIELDS.DATE:
      return (new Date(a.created_at).getTime() - new Date(b.created_at).getTime()) * multiplier;
    case FILE_MANAGER_SORT_FIELDS.SIZE:
      return (a.size - b.size) * multiplier;
    case FILE_MANAGER_SORT_FIELDS.TYPE:
      return (a.mime_type || '').localeCompare(b.mime_type || '') * multiplier;
    default:
      return 0;
  }
}

/** Client-side search (case-insensitive name), type filter and sort. */
export function processFiles(
  files: MediaFile[],
  searchQuery: string,
  filter: FilterOptions,
  sort: SortOptions,
): MediaFile[] {
  let processed = [...files];

  if (searchQuery) {
    const query = searchQuery.toLowerCase();
    processed = processed.filter((file) => file.name.toLowerCase().includes(query));
  }

  if (filter.type !== FILTER_TYPE.ALL) {
    processed = processed.filter((file) => matchesType(file, filter.type));
  }

  return processed.sort((a, b) => compareFiles(a, b, sort));
}
