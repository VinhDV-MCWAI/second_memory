'use client';

import {
  Upload,
  FolderPlus,
  Delete,
  Grid3x3,
  List,
  Search,
  Filter,
  ArrowUpDown,
  CheckSquare,
  Move,
  Copy,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from 'next-intl';
import { SORT_ORDER } from '@/shared/config/constant';
import { FILE_MANAGER_SORT_FIELDS, VIEW_MODE } from '@/shared/config/constant';
import type { ToolbarProps, SortField, FilterType } from '@/shared/types/file-manager.types';

export const Toolbar = ({
  viewMode,
  onViewModeChange,
  onUpload,
  onNewFolder,
  onDelete,
  onMove,
  onCopy,
  onSearchChange,
  searchQuery,
  selectedCount,
  filterOptions,
  onFilterChange,
  sortOptions,
  onSortChange,
}: ToolbarProps) => {
  const t = useTranslations('fileManager');
  
  const handleSort = (field: SortField) => {
    if (sortOptions.field === field) {
      onSortChange({
        ...sortOptions,
        order: sortOptions.order === SORT_ORDER.ASC ? SORT_ORDER.DESC : SORT_ORDER.ASC,
      });
    } else {
      onSortChange({
        field,
        order: SORT_ORDER.ASC,
      });
    }
  };

  return (
    <div className="flex flex-col gap-4 border-b border-border bg-background px-4 py-4">
      <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div className="flex flex-wrap items-center gap-2">
          <Button
            onClick={onUpload}
            variant="default"
            size="sm"
            className="gap-2"
          >
            <Upload className="h-4 w-4" />
            {t('upload')}
          </Button>
          <Button
            onClick={onNewFolder}
            variant="outline"
            size="sm"
            className="gap-2"
          >
            <FolderPlus className="h-4 w-4" />
            {t('newFolder')}
          </Button>
          
          {selectedCount > 0 && (
            <>
              <div className="mx-2 h-6 w-px bg-border" />
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <CheckSquare className="h-4 w-4" />
                <span>{t('selectedCount', { count: selectedCount })}</span>
              </div>
              <Button
                onClick={onMove}
                variant="outline"
                size="sm"
                className="gap-2"
              >
                <Move className="h-4 w-4" />
                {t('move')}
              </Button>
              <Button
                onClick={onCopy}
                variant="outline"
                size="sm"
                className="gap-2"
              >
                <Copy className="h-4 w-4" />
                {t('copy')}
              </Button>
              <Button
                onClick={onDelete}
                variant="destructive"
                size="sm"
                className="gap-2"
              >
                <Delete className="h-4 w-4" />
                {t('delete')}
              </Button>
            </>
          )}
        </div>

        <div className="flex items-center gap-2">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="outline" size="sm" className="gap-2">
                <Filter className="h-4 w-4" />
                {t('filter')}: {t(`filterType.${filterOptions.type}`)}
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuRadioGroup
                value={filterOptions.type}
                onValueChange={(value) =>
                  onFilterChange({ ...filterOptions, type: value as FilterType })
                }
              >
                <DropdownMenuRadioItem value="all">{t('filterType.all')}</DropdownMenuRadioItem>
                <DropdownMenuRadioItem value="images">{t('filterType.images')}</DropdownMenuRadioItem>
                <DropdownMenuRadioItem value="videos">{t('filterType.videos')}</DropdownMenuRadioItem>
                <DropdownMenuRadioItem value="documents">{t('filterType.documents')}</DropdownMenuRadioItem>
                <DropdownMenuRadioItem value="folders">{t('filterType.folders')}</DropdownMenuRadioItem>
              </DropdownMenuRadioGroup>
            </DropdownMenuContent>
          </DropdownMenu>

          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="outline" size="sm" className="gap-2">
                <ArrowUpDown className="h-4 w-4" />
                {t('sort')}
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => handleSort(FILE_MANAGER_SORT_FIELDS.NAME)}>
                {t('sortField.name')} {sortOptions.field === FILE_MANAGER_SORT_FIELDS.NAME && (sortOptions.order === SORT_ORDER.ASC ? '↑' : '↓')}
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleSort(FILE_MANAGER_SORT_FIELDS.DATE)}>
                {t('sortField.date')} {sortOptions.field === FILE_MANAGER_SORT_FIELDS.DATE && (sortOptions.order === SORT_ORDER.ASC ? '↑' : '↓')}
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleSort(FILE_MANAGER_SORT_FIELDS.SIZE)}>
                {t('sortField.size')} {sortOptions.field === FILE_MANAGER_SORT_FIELDS.SIZE && (sortOptions.order === SORT_ORDER.ASC ? '↑' : '↓')}
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleSort(FILE_MANAGER_SORT_FIELDS.TYPE)}>
                {t('sortField.type')} {sortOptions.field === FILE_MANAGER_SORT_FIELDS.TYPE && (sortOptions.order === SORT_ORDER.ASC ? '↑' : '↓')}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <div className="flex rounded-md border border-input">
            <Button
              onClick={() => onViewModeChange(VIEW_MODE.GRID)}
              variant={viewMode === VIEW_MODE.GRID ? 'secondary' : 'ghost'}
              size="sm"
              className="h-8 w-8 rounded-r-none px-0"
            >
              <Grid3x3 className="h-4 w-4" />
            </Button>
            <Button
              onClick={() => onViewModeChange(VIEW_MODE.LIST)}
              variant={viewMode === VIEW_MODE.LIST ? 'secondary' : 'ghost'}
              size="sm"
              className="h-8 w-8 rounded-l-none px-0"
            >
              <List className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>

      <div className="relative">
        <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t('searchPlaceholder')}
          value={searchQuery}
          onChange={(e) => onSearchChange(e.target.value)}
          className="pl-10"
        />
      </div>
    </div>
  );
};
