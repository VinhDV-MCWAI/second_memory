'use client';

import { ChevronDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslations } from 'next-intl';
import type { SidebarFolder, SidebarProps } from '@/shared/types/file-manager.types';
import { DEFAULT_SIDEBAR_FOLDERS } from '@/shared/config/constant';

export const Sidebar = ({
  currentPath,
  onPathChange,
  isOpen = true,
  onToggle,
  className,
}: SidebarProps) => {
  const t = useTranslations('fileManager');
  
  const FOLDERS: SidebarFolder[] = DEFAULT_SIDEBAR_FOLDERS.map(folder => ({
    id: folder.id,
    name: t(folder.nameKey),
    path: folder.path,
  }));

  if (!isOpen) return null;

  return (
    <aside className={`w-64 border-r border-border bg-background ${className || ''}`}>
      <div className="space-y-2 p-4">
        <div className="mb-6 flex items-center justify-between">
          <h2 className="text-lg font-semibold">{t('sidebar.title')}</h2>
          {onToggle && (
            <Button
              variant="ghost"
              size="sm"
              onClick={onToggle}
              className="h-8 w-8"
            >
              <ChevronDown className="h-4 w-4" />
            </Button>
          )}
        </div>

        <nav className="space-y-1">
          {FOLDERS.map((folder) => (
            <div key={folder.id}>
              <Button
                variant={currentPath === folder.path ? 'secondary' : 'ghost'}
                className="w-full justify-start"
                onClick={() => onPathChange(folder.path)}
              >
                <span className="truncate">{folder.name}</span>
              </Button>
            </div>
          ))}
        </nav>

        <div className="border-t border-border pt-4 mt-4">
          <h3 className="mb-2 text-sm font-medium text-muted-foreground">
            {t('sidebar.foldersList')}
          </h3>
          <div className="space-y-1">
            {FOLDERS.slice(0, 3).map((folder) => (
              <button
                key={folder.id}
                className="flex w-full items-center rounded-md px-2 py-1.5 text-sm hover:bg-accent"
                onClick={() => onPathChange(folder.path)}
              >
                <span className="text-base mr-2">📁</span>
                <span className="truncate text-left">{folder.name}</span>
              </button>
            ))}
          </div>
        </div>
      </div>
    </aside>
  );
};
