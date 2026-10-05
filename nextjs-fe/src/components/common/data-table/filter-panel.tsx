'use client';

import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Card } from '@/components/ui/card';
import { Search, X, Filter } from 'lucide-react';
import { useTranslations } from 'next-intl';
import { FilterField, FilterPanelProps } from '@/shared/types';

export type { FilterField };

export function FilterPanel({
  filters,
  onFilterChange,
  onReset,
  fields = [],
}: FilterPanelProps) {
  const [isExpanded, setIsExpanded] = useState(false);
  const [localFilters, setLocalFilters] = useState(filters);
  const t = useTranslations('filters');

  const handleChange = (key: string, value: unknown) => {
    setLocalFilters((prev) => ({
      ...prev,
      [key]: value,
    }));
  };

  const handleApply = () => {
    onFilterChange(localFilters);
  };

  const handleReset = () => {
    setLocalFilters({});
    onReset();
  };

  const activeFilterCount = Object.keys(filters).filter(
    (key) => filters[key] !== undefined && filters[key] !== ''
  ).length;

  return (
    <Card className="p-4 mb-6">
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-2">
          <Filter className="h-5 w-5 text-slate-600 dark:text-slate-400" />
          <h3 className="font-semibold text-slate-900 dark:text-white">
            {t('title')}
          </h3>
          {activeFilterCount > 0 && (
            <span className="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full">
              {activeFilterCount} {t('active')}
            </span>
          )}
        </div>
        <Button
          variant="ghost"
          size="sm"
          onClick={() => setIsExpanded(!isExpanded)}
        >
          {isExpanded ? t('hide') : t('show')}
        </Button>
      </div>

      {isExpanded && (
        <>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
            {fields.map((field) => (
              <div key={field.key} className="space-y-2">
                <Label htmlFor={field.key}>{field.label}</Label>
                {field.type === 'text' && (
                  <Input
                    id={field.key}
                    type="text"
                    placeholder={field.placeholder || `${t('searchPlaceholder').replace('{field}', field.label.toLowerCase())}`}
                    value={(localFilters[field.key] as string) || ''}
                    onChange={(e) => handleChange(field.key, e.target.value)}
                  />
                )}
                {field.type === 'select' && field.options && (
                  <Select
                    value={(localFilters[field.key] as string | number)?.toString() || ''}
                    onValueChange={(value) => handleChange(field.key, value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder={t('selectPlaceholder').replace('{field}', field.label.toLowerCase())} />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">{t('all')}</SelectItem>
                      {field.options.map((option) => (
                        <SelectItem
                          key={option.value}
                          value={option.value.toString()}
                        >
                          {option.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                )}
                {field.type === 'date' && (
                  <Input
                    id={field.key}
                    type="date"
                    value={(localFilters[field.key] as string) || ''}
                    onChange={(e) => handleChange(field.key, e.target.value)}
                  />
                )}
                {field.type === 'boolean' && (
                  <Select
                    value={(localFilters[field.key] as boolean)?.toString() || ''}
                    onValueChange={(value) =>
                      handleChange(field.key, value === 'true')
                    }
                  >
                    <SelectTrigger>
                      <SelectValue placeholder={t('all')} />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">{t('all')}</SelectItem>
                      <SelectItem value="true">{t('yes')}</SelectItem>
                      <SelectItem value="false">{t('no')}</SelectItem>
                    </SelectContent>
                  </Select>
                )}
              </div>
            ))}
          </div>

          <div className="flex items-center gap-2">
            <Button onClick={handleApply} size="sm">
              <Search className="h-4 w-4 mr-2" />
              {t('applyFilters')}
            </Button>
            <Button onClick={handleReset} variant="outline" size="sm">
              <X className="h-4 w-4 mr-2" />
              {t('clearAll')}
            </Button>
          </div>
        </>
      )}
    </Card>
  );
}
