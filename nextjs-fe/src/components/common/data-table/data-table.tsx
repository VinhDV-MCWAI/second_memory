'use client';

import { type ReactNode } from 'react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { ArrowUpDown, Edit, Trash2 } from 'lucide-react';
import { useTranslations } from 'next-intl';
import { Column, DataTableProps } from '@/shared/types';
import { LoadingOverlay } from '@/components/ui/loading';

export type { Column };

export function DataTable<T>({
  data,
  columns,
  loading = false,
  selectedIds = [],
  onSelectionChange,
  onSort,
  sortBy,
  onEdit,
  onDelete,
  showActions = true,
  idKey = 'id' as keyof T,
}: DataTableProps<T>) {
  const t = useTranslations('common');

  const handleSelectAll = (checked: boolean) => {
    if (!onSelectionChange) return;
    if (checked) {
      onSelectionChange(data.map((item) => item[idKey] as number));
    } else {
      onSelectionChange([]);
    }
  };

  const handleSelectOne = (id: number, checked: boolean) => {
    if (!onSelectionChange) return;
    if (checked) {
      onSelectionChange([...selectedIds, id]);
    } else {
      onSelectionChange(selectedIds.filter((selectedId) => selectedId !== id));
    }
  };

  const SortIcon = ({ column }: { column: string }) => (
    <ArrowUpDown
      className={`ml-2 h-4 w-4 inline ${
        sortBy === column ? 'text-blue-600' : 'text-gray-400'
      }`}
    />
  );

  // No early return for loading


  const content = data.length === 0 ? (
    <div className="text-center py-12 bg-slate-50 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800">
      <p className="text-slate-600 dark:text-slate-400">{t('noData')}</p>
    </div>
  ) : (
    <div className="rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow className="bg-slate-50 dark:bg-slate-900">
            {onSelectionChange && (
              <TableHead className="w-12">
                <Checkbox
                  checked={selectedIds.length === data.length && data.length > 0}
                  onCheckedChange={handleSelectAll}
                />
              </TableHead>
            )}
            {columns.map((column) => (
              <TableHead
                key={column.key}
                className={column.sortable && onSort ? 'cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800' : ''}
                onClick={() => column.sortable && onSort && onSort(column.key)}
              >
                {column.label}
                {column.sortable && onSort && <SortIcon column={column.key} />}
              </TableHead>
            ))}
            {showActions && (
              <TableHead className="text-right">{t('actions')}</TableHead>
            )}
          </TableRow>
        </TableHeader>
        <TableBody>
          {data.map((item) => {
            const id = item[idKey] as number;
            return (
              <TableRow
                key={id}
                className="hover:bg-slate-50 dark:hover:bg-slate-800"
              >
                {onSelectionChange && (
                  <TableCell>
                    <Checkbox
                      checked={selectedIds.includes(id)}
                      onCheckedChange={(checked) =>
                        handleSelectOne(id, checked as boolean)
                      }
                    />
                  </TableCell>
                )}
                {columns.map((column) => (
                  <TableCell key={column.key}>
                    {column.render
                      ? column.render(item)
                      : (item[column.key as keyof T] as ReactNode)}
                  </TableCell>
                ))}
                {showActions && (
                  <TableCell className="text-right space-x-2">
                    {onEdit && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => onEdit(id)}
                      >
                        <Edit className="h-4 w-4" />
                      </Button>
                    )}
                    {onDelete && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => onDelete(id)}
                        className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    )}
                  </TableCell>
                )}
              </TableRow>
            );
          })}
        </TableBody>
      </Table>
    </div>
  );

  return (
    <div className="relative min-h-[100px]">
      {loading && <LoadingOverlay variant="absolute" />}
      {content}
    </div>
  );
}
