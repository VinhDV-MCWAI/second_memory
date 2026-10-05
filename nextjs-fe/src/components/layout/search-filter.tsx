'use client';

import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useState, useCallback, useRef, useEffect } from 'react';
import { Search, X, Filter } from 'lucide-react';
import { cn } from "@/shared/utils";
import { UI_CONSTANTS, KEYBOARD_KEYS } from '@/shared/config';
import type { SearchFilterProps } from '@/shared/types/ui.types';
import { useTranslations } from 'next-intl';

export function SearchFilter({
  placeholder,
  onSearch,
  onFilterClick,
  showFilter = true,
  debounceMs = UI_CONSTANTS.DEBOUNCE_MS,
  className,
}: SearchFilterProps) {
  const tSearch = useTranslations('search');
  const finalPlaceholder = placeholder || tSearch('placeholder');
  const [searchValue, setSearchValue] = useState('');
  const [isSearching, setIsSearching] = useState(false);
  const timeoutRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, []);

  const handleSearch = useCallback(
    (value: string) => {
      setSearchValue(value);
      setIsSearching(true);

      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }

      timeoutRef.current = setTimeout(() => {
        onSearch?.(value);
        setIsSearching(false);
      }, debounceMs);
    },
    [onSearch, debounceMs]
  );

  const handleClear = useCallback(() => {
    setSearchValue('');
    setIsSearching(false);
    onSearch?.('');
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }
  }, [onSearch]);

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === KEYBOARD_KEYS.ESCAPE) {
      handleClear();
    }
  };

  return (
    <div className={cn('flex items-center gap-2', className)}>
      <div className="relative max-w-sm flex-1">
        <Search
          className={cn(
            'absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 transition-colors',
            isSearching
              ? 'text-blue-500 dark:text-blue-400'
              : 'text-slate-400'
          )}
        />
        <Input
          type="search"
          placeholder={finalPlaceholder}
          value={searchValue}
          onChange={(e) => handleSearch(e.target.value)}
          onKeyDown={handleKeyDown}
          className="pl-10 pr-10"
          aria-label="Search input"
        />
        {searchValue && (
          <button
            onClick={handleClear}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-300"
            aria-label="Clear search"
            type="button"
          >
            <X className="h-4 w-4" />
          </button>
        )}
        {isSearching && !searchValue && (
          <div className="absolute right-3 top-1/2 -translate-y-1/2">
            <div className="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-blue-500" />
          </div>
        )}
      </div>

      {showFilter && (
        <Button
          variant="outline"
          onClick={onFilterClick}
          className="gap-2"
          aria-label="Open filters"
        >
          <Filter className="h-4 w-4" />
          <span className="hidden sm:inline">{tSearch('filter')}</span>
        </Button>
      )}
    </div>
  );
}
