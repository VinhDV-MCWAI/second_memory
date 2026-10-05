'use client';

import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Save, Filter, Trash2, Star } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import toast from 'react-hot-toast';
import { useTranslations } from 'next-intl';
import type { SavedFilter, SavedFiltersProps } from '@/shared/types/data-table.types';

export type { SavedFilter } from '@/shared/types/data-table.types';

export function SavedFilters({
  currentFilters,
  onApplyFilter,
  storageKey = 'saved_filters',
  className,
}: SavedFiltersProps) {
  const t = useTranslations();
  const tCommon = useTranslations('common');
  const [savedFilters, setSavedFilters] = useState<SavedFilter[]>([]);
  const [saveDialogOpen, setSaveDialogOpen] = useState(false);
  const [filterName, setFilterName] = useState('');

  const loadFilters = () => {
    try {
      const stored = localStorage.getItem(storageKey);
      if (stored) {
        setSavedFilters(JSON.parse(stored));
      }
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (_error) {
      // Handle error silently
    }
  };

  useEffect(() => {
    loadFilters();
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const saveFilters = (filters: SavedFilter[]) => {
    try {
      localStorage.setItem(storageKey, JSON.stringify(filters));
      setSavedFilters(filters);
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (_error) {
      toast.error(t('savedFilters.failedToSaveFilter'));
    }
  };

  const handleSaveFilter = () => {
    if (!filterName.trim()) {
      toast.error(t('savedFilters.enterFilterName'));
      return;
    }

    const newFilter: SavedFilter = {
      id: Date.now().toString(),
      name: filterName,
      filters: currentFilters,
    };

    saveFilters([...savedFilters, newFilter]);
    setFilterName('');
    setSaveDialogOpen(false);
    toast.success(t('savedFilters.filterSaved'));
  };

  const handleDeleteFilter = (id: string) => {
    if (confirm(t('savedFilters.deleteFilterConfirm'))) {
      saveFilters(savedFilters.filter((f) => f.id !== id));
      toast.success(t('savedFilters.filterDeleted'));
    }
  };

  const handleSetDefault = (id: string) => {
    const updated = savedFilters.map((f) => ({
      ...f,
      isDefault: f.id === id,
    }));
    saveFilters(updated);
    toast.success(t('savedFilters.defaultFilterUpdated'));
  };

  const handleApplyFilter = (filter: SavedFilter) => {
    onApplyFilter(filter.filters);
    toast.success(t('savedFilters.appliedFilter', { name: filter.name }));
  };

  return (
    <div className={className}>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="outline">
            <Filter className="mr-2 h-4 w-4" />
            {t('savedFilters.savedFilters')}
            {savedFilters.length > 0 && (
              <Badge variant="secondary" className="ml-2">
                {savedFilters.length}
              </Badge>
            )}
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-64">
          {savedFilters.length === 0 ? (
            <div className="p-4 text-center text-sm text-muted-foreground">
              {t('savedFilters.noSavedFilters')}
            </div>
          ) : (
            savedFilters.map((filter) => (
              <DropdownMenuItem
                key={filter.id}
                className="flex items-center justify-between"
                onClick={() => handleApplyFilter(filter)}
              >
                <div className="flex items-center gap-2">
                  {filter.isDefault && <Star className="h-3 w-3 fill-yellow-400 text-yellow-400" />}
                  <span>{filter.name}</span>
                </div>
                <div className="flex gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleSetDefault(filter.id);
                    }}
                  >
                    <Star className="h-3 w-3" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleDeleteFilter(filter.id);
                    }}
                  >
                    <Trash2 className="h-3 w-3" />
                  </Button>
                </div>
              </DropdownMenuItem>
            ))
          )}
          <DropdownMenuSeparator />
          <DropdownMenuItem onClick={() => setSaveDialogOpen(true)}>
            <Save className="mr-2 h-4 w-4" />
            {t('savedFilters.saveCurrentFilter')}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <Dialog open={saveDialogOpen} onOpenChange={setSaveDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t('savedFilters.saveFilterTitle')}</DialogTitle>
            <DialogDescription>
              {t('savedFilters.saveFilterDescription')}
            </DialogDescription>
          </DialogHeader>
          <Input
            placeholder={t('savedFilters.filterName')}
            value={filterName}
            onChange={(e) => setFilterName(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && handleSaveFilter()}
          />
          <DialogFooter>
            <Button variant="outline" onClick={() => setSaveDialogOpen(false)}>
              {tCommon('cancel')}
            </Button>
            <Button onClick={handleSaveFilter}>{tCommon('save')}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
