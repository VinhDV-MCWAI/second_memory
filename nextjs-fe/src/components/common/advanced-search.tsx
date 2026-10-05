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
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Search, Plus, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useTranslations } from 'next-intl';
import type { AdvancedSearchProps, SearchCriteria } from '@/shared/types/data-table.types';

export function AdvancedSearch({ fields, onSearch, className }: AdvancedSearchProps) {
  const t = useTranslations('advancedSearch');
  const tCommon = useTranslations('common');
  const [open, setOpen] = useState(false);
  const [criteria, setCriteria] = useState<SearchCriteria[]>([]);

  const OPERATORS = {
    text: [
      { value: 'contains', label: t('contains') },
      { value: 'equals', label: t('equals') },
      { value: 'starts_with', label: t('startsWith') },
      { value: 'ends_with', label: t('endsWith') },
    ],
    number: [
      { value: 'equals', label: t('equals') },
      { value: 'greater_than', label: t('greaterThan') },
      { value: 'less_than', label: t('lessThan') },
      { value: 'between', label: t('between') },
    ],
    date: [
      { value: 'equals', label: t('on') },
      { value: 'before', label: t('before') },
      { value: 'after', label: t('after') },
      { value: 'between', label: t('between') },
    ],
    select: [
      { value: 'equals', label: t('equals') },
      { value: 'not_equals', label: t('notEquals') },
    ],
  };

  const addCriteria = () => {
    setCriteria([
      ...criteria,
      { field: fields[0]?.key || '', operator: 'contains', value: '' },
    ]);
  };

  const removeCriteria = (index: number) => {
    setCriteria(criteria.filter((_, i) => i !== index));
  };

  const updateCriteria = (index: number, updates: Partial<SearchCriteria>) => {
    const newCriteria = [...criteria];
    newCriteria[index] = { ...newCriteria[index], ...updates };
    setCriteria(newCriteria);
  };

  const handleSearch = () => {
    onSearch(criteria.filter((c) => c.value));
    setOpen(false);
  };

  const handleReset = () => {
    setCriteria([]);
    onSearch([]);
  };

  const getOperators = (fieldKey: string) => {
    const field = fields.find((f) => f.key === fieldKey);
    return OPERATORS[field?.type || 'text'];
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" className={className}>
          <Search className="mr-2 h-4 w-4" />
          {t('title')}
          {criteria.length > 0 && (
            <Badge variant="secondary" className="ml-2">
              {criteria.length}
            </Badge>
          )}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>{t('title')}</DialogTitle>
          <DialogDescription>
            {t('description')}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          {criteria.map((criterion, index) => {
            const field = fields.find((f) => f.key === criterion.field);
            return (
              <div key={index} className="flex gap-2">
                <div className="flex-1 space-y-2">
                  <Label>{t('field')}</Label>
                  <Select
                    value={criterion.field}
                    onValueChange={(value) =>
                      updateCriteria(index, { field: value })
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {fields.map((f) => (
                        <SelectItem key={f.key} value={f.key}>
                          {f.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="flex-1 space-y-2">
                  <Label>{t('operator')}</Label>
                  <Select
                    value={criterion.operator}
                    onValueChange={(value) =>
                      updateCriteria(index, { operator: value })
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {getOperators(criterion.field).map((op) => (
                        <SelectItem key={op.value} value={op.value}>
                          {op.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="flex-1 space-y-2">
                  <Label>{t('value')}</Label>
                  {field?.type === 'select' ? (
                    <Select
                      value={criterion.value}
                      onValueChange={(value) =>
                        updateCriteria(index, { value })
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {field.options?.map((opt) => (
                          <SelectItem key={opt.value} value={String(opt.value)}>
                            {opt.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  ) : (
                    <Input
                      type={field?.type || 'text'}
                      value={criterion.value}
                      onChange={(e) =>
                        updateCriteria(index, { value: e.target.value })
                      }
                    />
                  )}
                </div>

                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => removeCriteria(index)}
                  className="mt-8"
                >
                  <X className="h-4 w-4" />
                </Button>
              </div>
            );
          })}

          <Button
            variant="outline"
            onClick={addCriteria}
            className="w-full"
          >
            <Plus className="mr-2 h-4 w-4" />
            {t('addCriteria')}
          </Button>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={handleReset}>
            {tCommon('reset')}
          </Button>
          <Button onClick={handleSearch}>{tCommon('search')}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
