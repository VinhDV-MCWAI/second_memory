'use client';

import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import { useTranslations } from 'next-intl';
import { PAGINATION } from '@/shared/config/constant';
import { PaginationProps } from '@/shared/types';

export function Pagination({
  pagination,
  page,
  onPageChange,
  perPage,
  onPerPageChange,
}: PaginationProps) {
  const { currentPage, lastPage, total, from, to } = pagination;
  const t = useTranslations('table');

  return (
    <div className="flex items-center justify-between px-2 py-4">
      {/* Info */}
      <div className="flex items-center gap-4">
        <div className="text-sm text-slate-600 dark:text-slate-400">
          {t('showing')} <span className="font-medium">{from}</span> {t('to')}{' '}
          <span className="font-medium">{to}</span> {t('of')}{' '}
          <span className="font-medium">{total}</span> {t('results')}
        </div>

        {/* Per Page Selector */}
        <div className="flex items-center gap-2">
          <span className="text-sm text-slate-600 dark:text-slate-400">
            {t('rowsPerPage')}
          </span>
          <Select
            value={perPage.toString()}
            onValueChange={(value) => onPerPageChange(Number(value))}
          >
            <SelectTrigger className="w-20">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {PAGINATION.PER_PAGE_OPTIONS.map((option) => (
                <SelectItem key={option} value={option.toString()}>
                  {option}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      {/* Navigation */}
      <div className="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          onClick={() => onPageChange(PAGINATION.DEFAULT_PAGE)}
          disabled={currentPage === PAGINATION.DEFAULT_PAGE}
        >
          <ChevronsLeft className="h-4 w-4" />
        </Button>
        <Button
          variant="outline"
          size="sm"
          onClick={() => onPageChange(page - 1)}
          disabled={currentPage === PAGINATION.DEFAULT_PAGE}
        >
          <ChevronLeft className="h-4 w-4" />
        </Button>

        <div className="flex items-center gap-1 px-2">
          <span className="text-sm text-slate-600 dark:text-slate-400">
            {t('page')}{' '}
            <span className="font-medium">
              {currentPage}
            </span>{' '}
            {t('of')}{' '}
            <span className="font-medium">
              {lastPage}
            </span>
          </span>
        </div>

        <Button
          variant="outline"
          size="sm"
          onClick={() => onPageChange(page + 1)}
          disabled={currentPage === lastPage}
        >
          <ChevronRight className="h-4 w-4" />
        </Button>
        <Button
          variant="outline"
          size="sm"
          onClick={() => onPageChange(lastPage)}
          disabled={currentPage === lastPage}
        >
          <ChevronsRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}
