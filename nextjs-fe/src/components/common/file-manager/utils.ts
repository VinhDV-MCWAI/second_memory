import {
  File,
  FileText,
  FileImage,
  FileVideo,
  FileAudio,
  FileArchive,
  FileCode,
  Folder,
} from 'lucide-react';
import { SORT_ORDER, MIME_TYPE_PREFIX } from '@/shared/config/constant';
import { FILTER_TYPE, FILE_TYPE, FILE_SIZE_UNITS, MIME_TYPE_LABELS } from '@/shared/config/constant';
import type { MediaFile, FilterOptions, SortOptions } from '@/shared/types/file-manager.types';

export const formatFileSize = (bytes: number): string => {
  if (bytes === 0 || bytes === null || bytes === undefined) return '0 B';

  const k = 1024;
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + FILE_SIZE_UNITS[i];
};

export const getFileIcon = (mimeType: string | null | undefined) => {
  // Handle null/undefined mime_type (e.g., for folders)
  if (!mimeType) {
    return File;
  }
  
  if (mimeType === 'folder' || mimeType === 'application/vnd.google-apps.folder') return Folder;
  if (mimeType.startsWith(MIME_TYPE_PREFIX.IMAGE)) return FileImage;
  if (mimeType.startsWith(MIME_TYPE_PREFIX.VIDEO)) return FileVideo;
  if (mimeType.startsWith('audio/')) return FileAudio;
  if (
    mimeType.includes('pdf') ||
    mimeType.includes('word') ||
    mimeType.includes('sheet') ||
    mimeType.includes('document')
  ) {
    return FileText;
  }
  if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('compressed')) {
    return FileArchive;
  }
  if (mimeType.startsWith('text/') || mimeType.includes('json') || mimeType.includes('xml')) return FileCode;

  return File;
};

export const getMimeTypeLabel = (mimeType: string | null | undefined): string => {
  // Handle null/undefined mime_type (e.g., for folders)
  if (!mimeType) {
    return 'Folder';
  }
  
  return MIME_TYPE_LABELS[mimeType] || mimeType.split('/')[1]?.toUpperCase() || 'File';
};

export const filterFiles = (
  files: MediaFile[],
  searchQuery: string,
  options: FilterOptions,
) => {
  return files.filter((file) => {
    // Search query
    if (searchQuery && !file.name.toLowerCase().includes(searchQuery.toLowerCase())) {
      return false;
    }

    // Type filter
    if (options.type !== FILTER_TYPE.ALL) {
      if (options.type === FILTER_TYPE.FOLDERS && file.type !== FILE_TYPE.FOLDER) return false;
      if (options.type === FILTER_TYPE.IMAGES && (file.type === FILE_TYPE.FOLDER || !file.mime_type || !file.mime_type.startsWith(MIME_TYPE_PREFIX.IMAGE))) return false;
      if (options.type === FILTER_TYPE.VIDEOS && (file.type === FILE_TYPE.FOLDER || !file.mime_type || !file.mime_type.startsWith(MIME_TYPE_PREFIX.VIDEO))) return false;
      if (options.type === FILTER_TYPE.DOCUMENTS && (file.type === FILE_TYPE.FOLDER || !file.mime_type || file.mime_type.startsWith(MIME_TYPE_PREFIX.IMAGE) || file.mime_type.startsWith(MIME_TYPE_PREFIX.VIDEO))) return false;
    }

    // Date filter
    if (options.dateFrom && new Date(file.created_at) < options.dateFrom) return false;
    if (options.dateTo && new Date(file.created_at) > options.dateTo) return false;

    // Size filter
    if (options.minSize && file.size < options.minSize) return false;
    if (options.maxSize && file.size > options.maxSize) return false;

    return true;
  });
};

export const sortFiles = (
  files: MediaFile[],
  options: SortOptions,
) => {
  const sorted = [...files];
  const { field, order } = options;
  const multiplier = order === SORT_ORDER.ASC ? 1 : -1;

  sorted.sort((a, b) => {
    switch (field) {
      case 'name':
        return a.name.localeCompare(b.name) * multiplier;
      case 'date':
        return (new Date(a.created_at).getTime() - new Date(b.created_at).getTime()) * multiplier;
      case 'size':
        return (a.size - b.size) * multiplier;
      case 'type':
        return a.mime_type.localeCompare(b.mime_type) * multiplier;
      default:
        return 0;
    }
  });

  return sorted;
};

export const isValidFileName = (name: string): boolean => {
  // Check for invalid characters (Windows/Unix common restrictions)
  const invalidChars = /[<>:"/\\|?*]/;
  return name.length > 0 && name.length <= 255 && !invalidChars.test(name);
};

export const buildBreadcrumb = (path: string) => {
  const parts = path.split('/').filter(Boolean);
  return parts.map((part, index) => ({
    label: part,
    path: '/' + parts.slice(0, index + 1).join('/'),
  }));
};
