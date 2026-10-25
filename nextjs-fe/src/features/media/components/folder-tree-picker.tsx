'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { ChevronDown, ChevronRight, Folder, FolderOpen } from 'lucide-react';
import { API_ENDPOINTS } from '@/shared/api';
import { useApiData } from '@/shared/hooks/use-api-data';
import type { MediaItem } from '@/shared/types/models';
import { cn } from '@/shared/utils';
import { ROOT } from '../lib/media-paths';

/** Folders come first in a listing, so one page of this size holds every sub-folder in practice. */
const FOLDERS_PAGE_SIZE = 100;

interface FolderTreePickerProps {
  value: string;
  onChange: (folder: string) => void;
  /** A folder that may not be picked, nor anything inside it (a folder cannot move into itself). */
  disabledPath?: string;
}

/**
 * The folders of the file manager as a tree to pick one from. A folder's sub-folders load when it
 * is opened; locked folders the admin may not use are left out by the API (ADR-0017).
 */
export function FolderTreePicker({ value, onChange, disabledPath }: FolderTreePickerProps) {
  const t = useTranslations('media');
  return (
    <div
      role="tree"
      aria-label={t('targetFolder')}
      className="max-h-72 overflow-auto rounded-md border p-1"
    >
      <FolderNode
        path={ROOT}
        name={t('topLevel')}
        depth={0}
        value={value}
        onChange={onChange}
        disabledPath={disabledPath}
        initiallyOpen
      />
    </div>
  );
}

interface FolderNodeProps extends FolderTreePickerProps {
  path: string;
  name: string;
  depth: number;
  initiallyOpen?: boolean;
}

const isInside = (path: string, folder: string) => path === folder || path.startsWith(`${folder}/`);

function FolderNode({
  path,
  name,
  depth,
  value,
  onChange,
  disabledPath,
  initiallyOpen = false,
}: FolderNodeProps) {
  const t = useTranslations('media');
  const [open, setOpen] = useState(initiallyOpen);
  const { data, loading } = useApiData<MediaItem>(API_ENDPOINTS.MEDIA.BASE, {
    per_page: FOLDERS_PAGE_SIZE,
    filters: { folder: path },
    enabled: open,
  });
  const folders = data.filter((item) => !item.is_file);
  const disabled = disabledPath !== undefined && isInside(path, disabledPath);
  const selected = value === path;
  const Toggle = open ? ChevronDown : ChevronRight;
  const Icon = open ? FolderOpen : Folder;

  return (
    <div role="treeitem" aria-expanded={open} aria-selected={selected}>
      <div className="flex items-center" style={{ paddingLeft: depth * 16 }}>
        <button
          type="button"
          className="rounded p-1 text-muted-foreground hover:bg-muted"
          aria-label={open ? t('collapse', { name }) : t('expand', { name })}
          onClick={() => setOpen(!open)}
        >
          <Toggle className="h-4 w-4" />
        </button>
        <button
          type="button"
          disabled={disabled}
          onClick={() => onChange(path)}
          className={cn(
            'flex flex-1 items-center gap-2 rounded px-2 py-1 text-left text-sm',
            selected ? 'bg-primary text-primary-foreground' : 'hover:bg-muted',
            disabled && 'cursor-not-allowed opacity-50',
          )}
        >
          <Icon className="h-4 w-4 shrink-0" />
          <span className="truncate">{name}</span>
        </button>
      </div>
      {open && (
        <div role="group">
          {loading && folders.length === 0 ? (
            <p
              className="py-1 text-xs text-muted-foreground"
              style={{ paddingLeft: (depth + 1) * 16 + 28 }}
            >
              {t('loadingFolders')}
            </p>
          ) : (
            folders.map((folder) => (
              <FolderNode
                key={folder.id}
                path={folder.path}
                name={folder.name}
                depth={depth + 1}
                value={value}
                onChange={onChange}
                disabledPath={disabledPath}
              />
            ))
          )}
        </div>
      )}
    </div>
  );
}
