'use client';

import { useTranslations } from 'next-intl';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useApiData } from '@/shared/hooks/use-api-data';
import { API_ENDPOINTS } from '@/shared/api';
import type { Tag } from '@/shared/types/models';

/** All tags fit on one page: the ledger has a few dozen (REQ-002 volume). */
const TAG_PAGE_SIZE = 100;

interface TagPickerProps {
  value: number[];
  onChange: (ids: number[]) => void;
}

/** Checkbox list of every tag; the selection is a list of tag ids. */
export function TagPicker({ value, onChange }: TagPickerProps) {
  const t = useTranslations('ledger');
  const { data: tags } = useApiData<Tag>(API_ENDPOINTS.LEDGER.TAG, { per_page: TAG_PAGE_SIZE });

  if (tags.length === 0) {
    return <p className="text-sm text-muted-foreground">{t('noTags')}</p>;
  }

  const toggle = (id: number, checked: boolean) =>
    onChange(checked ? [...value, id] : value.filter((selected) => selected !== id));

  return (
    <div className="flex flex-wrap gap-4">
      {tags.map((tag) => (
        <div key={tag.id} className="flex items-center gap-2">
          <Checkbox
            id={`tag-${tag.id}`}
            checked={value.includes(tag.id)}
            onCheckedChange={(checked) => toggle(tag.id, checked === true)}
          />
          <Label htmlFor={`tag-${tag.id}`}>{tag.name}</Label>
        </div>
      ))}
    </div>
  );
}
