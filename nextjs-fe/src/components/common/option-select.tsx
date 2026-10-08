'use client';

import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export interface SelectOption {
  value: string | number;
  label: string;
}

interface OptionSelectProps {
  value: string | number | null | undefined;
  onChange: (value: string) => void;
  options: SelectOption[];
}

/** Select over a fixed option list; remounts when the value changes (e.g. after `reset`). */
export function OptionSelect({ value, onChange, options }: OptionSelectProps) {
  return (
    <Select
      key={String(value)}
      value={value !== undefined && value !== null ? String(value) : ''}
      onValueChange={onChange}
    >
      <SelectTrigger>
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        {options.map((option) => (
          <SelectItem key={option.value} value={String(option.value)}>
            {option.label}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}
