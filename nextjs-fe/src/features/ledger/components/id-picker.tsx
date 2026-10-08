'use client';

import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useApiData } from '@/shared/hooks/use-api-data';

/** Tags and skills fit on one page: the ledger has a few dozen of each (REQ-002 volume). */
const PICKER_PAGE_SIZE = 100;

interface IdPickerProps {
  /** List endpoint of records with `id` and `name`, e.g. `API_ENDPOINTS.LEDGER.TAG`. */
  endpoint: string;
  /** Shown instead of the list when there is nothing to pick. */
  emptyMessage: string;
  value: number[];
  onChange: (ids: number[]) => void;
}

/** Checkbox list of every record of an endpoint; the selection is a list of ids. */
export function IdPicker({ endpoint, emptyMessage, value, onChange }: IdPickerProps) {
  const { data: records } = useApiData<{ id: number; name: string }>(endpoint, {
    per_page: PICKER_PAGE_SIZE,
  });

  if (records.length === 0) {
    return <p className="text-sm text-muted-foreground">{emptyMessage}</p>;
  }

  const toggle = (id: number, checked: boolean) =>
    onChange(checked ? [...value, id] : value.filter((selected) => selected !== id));

  return (
    <div className="flex flex-wrap gap-4">
      {records.map((record) => {
        const id = `${endpoint}-${record.id}`;
        return (
          <div key={record.id} className="flex items-center gap-2">
            <Checkbox
              id={id}
              checked={value.includes(record.id)}
              onCheckedChange={(checked) => toggle(record.id, checked === true)}
            />
            <Label htmlFor={id}>{record.name}</Label>
          </div>
        );
      })}
    </div>
  );
}
