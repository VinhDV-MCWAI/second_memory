'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { Check, ChevronDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/shared/utils';
import { flattenTree, matchesQuery, type CategoryRow } from '../lib/category-tree';

/** Indent of one level in the list, in pixels (levels are dynamic, so not a Tailwind class) */
const LEVEL_INDENT = 16;

type Selection =
  | {
      mode: 'single';
      value: number | null;
      onChange: (id: number | null) => void;
      noneLabel: string;
    }
  | { mode: 'multiple'; value: number[]; onChange: (ids: number[]) => void };

type CategoryTreeSelectProps = Selection & {
  /** Id of the trigger, for the field's label */
  id: string;
  categories: CategoryRow[];
  /** Categories shown greyed out, e.g. a category and its branch when picking its parent */
  disabledIds?: Set<number>;
  invalid?: boolean;
};

/**
 * Pick one category (a parent) or several (an article's) from the category tree: indented by
 * level, with a search over the whole path that ignores accents. With several, "Select all" and
 * "Clear" act on the categories the search shows.
 */
export function CategoryTreeSelect(props: CategoryTreeSelectProps) {
  const { id, categories, disabledIds, invalid } = props;
  const t = useTranslations('content');
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');

  const nodes = flattenTree(categories);
  const shown = query.trim() === '' ? nodes : nodes.filter((node) => matchesQuery(node, query));
  const pickable = shown.filter((node) => !disabledIds?.has(node.row.id));
  const pathOf = (categoryId: number) =>
    nodes.find((node) => node.row.id === categoryId)?.path.join(' › ') ?? '';

  const selectedIds = props.mode === 'single' ? [] : props.value;
  const summary =
    props.mode === 'single'
      ? props.value === null
        ? props.noneLabel
        : pathOf(props.value)
      : props.value.length === 0
        ? t('pickCategories')
        : props.value
            .map((value) => nodes.find((node) => node.row.id === value)?.row.name)
            .join(', ');

  const toggle = (categoryId: number, checked: boolean) => {
    if (props.mode !== 'multiple') return;
    props.onChange(
      checked
        ? [...props.value, categoryId]
        : props.value.filter((selected) => selected !== categoryId),
    );
  };
  const pick = (categoryId: number | null) => {
    if (props.mode !== 'single') return;
    props.onChange(categoryId);
    setOpen(false);
  };
  const selectShown = () => {
    if (props.mode !== 'multiple') return;
    props.onChange([...new Set([...props.value, ...pickable.map((node) => node.row.id)])]);
  };
  const clearShown = () => {
    if (props.mode !== 'multiple') return;
    const cleared = new Set(shown.map((node) => node.row.id));
    props.onChange(props.value.filter((selected) => !cleared.has(selected)));
  };

  return (
    <Popover
      open={open}
      onOpenChange={(next) => {
        setOpen(next);
        if (!next) setQuery('');
      }}
    >
      <PopoverTrigger asChild>
        <Button
          id={id}
          type="button"
          variant="outline"
          role="combobox"
          aria-expanded={open}
          aria-invalid={invalid}
          className="h-auto min-h-9 w-full justify-between text-left font-normal whitespace-normal"
        >
          <span className="line-clamp-2">{summary}</span>
          <ChevronDown className="h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent align="start" className="w-[var(--radix-popover-trigger-width)] p-2">
        <Input
          autoFocus
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder={t('searchCategories')}
          aria-label={t('searchCategories')}
        />
        {props.mode === 'multiple' && (
          <div className="mt-2 flex items-center gap-2 text-xs">
            <Button type="button" variant="ghost" size="sm" onClick={selectShown}>
              {t('selectShown')}
            </Button>
            <Button type="button" variant="ghost" size="sm" onClick={clearShown}>
              {t('clearShown')}
            </Button>
            <span className="ml-auto text-muted-foreground">
              {t('selectedCount', { count: props.value.length })}
            </span>
          </div>
        )}
        <ul
          className="mt-2 max-h-72 overflow-auto"
          role="listbox"
          aria-multiselectable={props.mode === 'multiple'}
        >
          {props.mode === 'single' && query.trim() === '' && (
            <li>
              <SingleRow
                label={props.noneLabel}
                selected={props.value === null}
                onClick={() => pick(null)}
              />
            </li>
          )}
          {shown.length === 0 && (
            <li className="px-2 py-1.5 text-sm text-muted-foreground">{t('noCategoryMatches')}</li>
          )}
          {shown.map((node) => {
            const disabled = disabledIds?.has(node.row.id) ?? false;
            // While searching, the path says where a match sits instead of the indent
            const label = query.trim() === '' ? node.row.name : node.path.join(' › ');
            const indent = query.trim() === '' ? node.level * LEVEL_INDENT : 0;
            return (
              <li key={node.row.id} style={{ paddingLeft: indent }}>
                {props.mode === 'single' ? (
                  <SingleRow
                    label={label}
                    selected={props.value === node.row.id}
                    disabled={disabled}
                    onClick={() => pick(node.row.id)}
                  />
                ) : (
                  <label
                    className={cn(
                      'flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-muted',
                      disabled && 'cursor-not-allowed opacity-50',
                    )}
                  >
                    <Checkbox
                      checked={selectedIds.includes(node.row.id)}
                      disabled={disabled}
                      onCheckedChange={(checked) => toggle(node.row.id, checked === true)}
                    />
                    {label}
                  </label>
                )}
              </li>
            );
          })}
        </ul>
      </PopoverContent>
    </Popover>
  );
}

function SingleRow({
  label,
  selected,
  disabled = false,
  onClick,
}: {
  label: string;
  selected: boolean;
  disabled?: boolean;
  onClick: () => void;
}) {
  return (
    <button
      type="button"
      role="option"
      aria-selected={selected}
      disabled={disabled}
      onClick={onClick}
      className="flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-sm hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50"
    >
      <Check className={cn('h-4 w-4 shrink-0', selected ? 'opacity-100' : 'opacity-0')} />
      {label}
    </button>
  );
}
