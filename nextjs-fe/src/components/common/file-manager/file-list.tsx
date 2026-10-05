'use client';

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { MoreHorizontal, ArrowUpDown, Folder } from 'lucide-react';
import type { MediaFile, SortField } from '@/shared/types/file-manager.types';
import { formatFileSize, getFileIcon, getMimeTypeLabel } from './utils';
import { format } from 'date-fns';
import { FileContextMenu } from './context-menu';
import { useTranslations } from 'next-intl';
import { DATE_FORMATS, SORT_ORDER, FILE_TYPE, FILE_MANAGER_SORT_FIELDS } from '@/shared/config/constant';
import type { FileListProps } from '@/shared/types/file-manager.types';

export const FileList = ({
  files,
  selectedFiles,
  onSelect,
  onSelectAll,
  onFileClick,
  onNavigate,
  isLoading = false,
  sortField,
  sortOrder,
  onSort,
  onPreview,
  onRename,
  onMove,
  onCopy,
  onDelete,
  onDownload,
}: FileListProps) => {
  const t = useTranslations('fileManager');

  if (isLoading) {
    return <div className="py-8 text-center text-muted-foreground">{t('loading')}</div>;
  }

  if (files.length === 0) {
    return (
      <div className="py-8 text-center text-muted-foreground">
        {t('noFiles')}
      </div>
    );
  }

  const handleSelectAll = (checked: boolean) => {
    onSelectAll(checked);
  };

  const handleDoubleClick = (file: MediaFile) => {
    if (file.type === FILE_TYPE.FOLDER) {
      onNavigate(file.folder_path === '/' ? `/${file.name}` : `${file.folder_path}/${file.name}`);
    } else {
      onPreview(file);
    }
  };

  const renderSortIcon = (field: SortField) => {
    if (sortField !== field) return <ArrowUpDown className="ml-2 h-4 w-4 opacity-50" />;
    return <ArrowUpDown className={`ml-2 h-4 w-4 ${sortOrder === SORT_ORDER.DESC ? 'rotate-180' : ''}`} />;
  };

  return (
    <div className="rounded-md border border-border bg-background">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead className="w-12">
              <Checkbox
                checked={
                  files.length > 0 &&
                  selectedFiles.length === files.length
                }
                onCheckedChange={(checked: boolean | 'indeterminate') => {
                  if (checked === 'indeterminate') return;
                  handleSelectAll(checked === true);
                }}
              />
            </TableHead>
            <TableHead
              className="cursor-pointer hover:bg-muted/50"
              onClick={() => onSort?.(FILE_MANAGER_SORT_FIELDS.NAME)}
            >
              <div className="flex items-center">
                {t('name')} {renderSortIcon(FILE_MANAGER_SORT_FIELDS.NAME)}
              </div>
            </TableHead>
            <TableHead 
              className="cursor-pointer hover:bg-muted/50"
              onClick={() => onSort?.(FILE_MANAGER_SORT_FIELDS.TYPE)}
            >
              <div className="flex items-center">
                {t('type')} {renderSortIcon(FILE_MANAGER_SORT_FIELDS.TYPE)}
              </div>
            </TableHead>
            <TableHead 
              className="text-right cursor-pointer hover:bg-muted/50"
              onClick={() => onSort?.(FILE_MANAGER_SORT_FIELDS.SIZE)}
            >
              <div className="flex items-center justify-end">
                {t('size')} {renderSortIcon(FILE_MANAGER_SORT_FIELDS.SIZE)}
              </div>
            </TableHead>
            <TableHead 
              className="cursor-pointer hover:bg-muted/50"
              onClick={() => onSort?.(FILE_MANAGER_SORT_FIELDS.DATE)}
            >
              <div className="flex items-center">
                {t('created')} {renderSortIcon(FILE_MANAGER_SORT_FIELDS.DATE)}
              </div>
            </TableHead>
            <TableHead className="w-12"></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {files.map((file) => {
            const isSelected = selectedFiles.includes(file.id);
            const Icon = getFileIcon(file.mime_type);

            return (
              <FileContextMenu
                key={file.id}
                file={file}
                onPreview={onPreview}
                onRename={onRename}
                onMove={onMove}
                onCopy={onCopy}
                onDelete={onDelete}
                onDownload={onDownload}
              >
                <TableRow
                  className={`cursor-pointer hover:bg-accent/50 ${isSelected ? 'bg-accent' : ''}`}
                  onClick={() => onFileClick(file)}
                  onDoubleClick={() => handleDoubleClick(file)}
                >
                  <TableCell onClick={(e) => e.stopPropagation()}>
                    <Checkbox
                      checked={isSelected}
                      onCheckedChange={(checked: boolean | 'indeterminate') => {
                        onSelect(file.id, checked === true);
                      }}
                    />
                  </TableCell>
                  <TableCell className="font-medium">
                    <div className="flex items-center gap-3">
                      {file.type === FILE_TYPE.FOLDER ? (
                        <Folder className="h-5 w-5 text-blue-500" fill="currentColor" />
                      ) : (
                        <Icon className="h-5 w-5 text-muted-foreground" />
                      )}
                      <span className="truncate max-w-[200px] md:max-w-[300px]" title={file.name}>
                        {file.name}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell className="text-muted-foreground">
                    {getMimeTypeLabel(file.mime_type)}
                  </TableCell>
                  <TableCell className="text-right text-muted-foreground">
                    {formatFileSize(file.size)}
                  </TableCell>
                  <TableCell className="text-muted-foreground">
                    {format(new Date(file.created_at), DATE_FORMATS.LONG)}
                  </TableCell>
                  <TableCell onClick={(e) => e.stopPropagation()}>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                          <span className="sr-only">{t('openMenu')}</span>
                          <MoreHorizontal className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => onPreview(file)}>
                          {t('preview')}
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => onDownload?.(file)}>
                          {t('download')}
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => onRename(file)}>
                          {t('rename')}
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => onMove(file)}>
                          {t('move')}
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => onCopy(file)}>
                          {t('copy')}
                        </DropdownMenuItem>
                        <DropdownMenuItem 
                          onClick={() => onDelete(file)}
                          className="text-red-600 focus:text-red-600"
                        >
                          {t('delete')}
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </FileContextMenu>
            );
          })}
        </TableBody>
      </Table>
    </div>
  );
};
