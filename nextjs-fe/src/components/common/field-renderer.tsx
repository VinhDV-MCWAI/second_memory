'use client';

import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from "@/shared/utils";
import { useTranslations } from 'next-intl';
import type { FieldRendererProps } from '@/shared/types/data-table.types';

export type { FieldType, FieldConfig } from '@/shared/types/data-table.types';

export function FieldRenderer({ field, value, onChange, error }: FieldRendererProps) {
  const t = useTranslations('common');
  
  const renderField = () => {
    switch (field.type) {
      case 'text':
      case 'email':
      case 'password':
      case 'number':
        return (
          <Input
            type={field.type}
            name={field.name}
            placeholder={field.placeholder}
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            disabled={field.disabled}
            required={field.required}
            min={field.min}
            max={field.max}
            className={cn(error && 'border-red-500', field.className)}
          />
        );

      case 'textarea':
        return (
          <Textarea
            name={field.name}
            placeholder={field.placeholder}
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            disabled={field.disabled}
            required={field.required}
            rows={field.rows || 4}
            className={cn(error && 'border-red-500', field.className)}
          />
        );

      case 'select':
        return (
          <Select
            value={value?.toString() || ''}
            onValueChange={onChange}
            disabled={field.disabled}
            required={field.required}
          >
            <SelectTrigger className={cn(error && 'border-red-500', field.className)}>
              <SelectValue placeholder={field.placeholder || `Select ${field.label}`} />
            </SelectTrigger>
            <SelectContent>
              {field.options?.map((option) => (
                <SelectItem
                  key={option.value}
                  value={option.value.toString()}
                  disabled={option.disabled}
                >
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        );

      case 'checkbox':
        return (
          <div className="flex items-center space-x-2">
            <Checkbox
              id={field.name}
              checked={!!value}
              onCheckedChange={onChange}
              disabled={field.disabled}
              className={cn(error && 'border-red-500')}
            />
            <Label
              htmlFor={field.name}
              className="text-sm font-normal cursor-pointer"
            >
              {field.label}
            </Label>
          </div>
        );

      case 'date':
      case 'datetime':
        return (
          <Input
            type={field.type === 'datetime' ? 'datetime-local' : 'date'}
            name={field.name}
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            disabled={field.disabled}
            required={field.required}
            className={cn(error && 'border-red-500', field.className)}
          />
        );

      case 'file':
      case 'image':
        return (
          <div className="space-y-2">
            <Input
              type="file"
              name={field.name}
              accept={field.accept || (field.type === 'image' ? 'image/*' : undefined)}
              onChange={(e) => {
                const file = e.target.files?.[0];
                onChange(file || null);
              }}
              disabled={field.disabled}
              required={field.required}
              className={cn(error && 'border-red-500', field.className)}
            />
            {value && typeof value === 'string' && field.type === 'image' ? (
              <div className="mt-2">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={value}
                  alt={t('preview')}
                  className="h-32 w-32 object-cover rounded-md border"
                />
              </div>
            ) : null}
          </div>
        );

      default:
        return (
          <Input
            type="text"
            name={field.name}
            placeholder={field.placeholder}
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            disabled={field.disabled}
            required={field.required}
            className={cn(error && 'border-red-500', field.className)}
          />
        );
    }
  };

  // For checkbox, we don't show label separately
  if (field.type === 'checkbox') {
    return (
      <div className="space-y-2">
        {renderField()}
        {field.description && (
          <p className="text-sm text-muted-foreground">{field.description}</p>
        )}
        {error && <p className="text-sm text-red-500">{error}</p>}
      </div>
    );
  }

  return (
    <div className="space-y-2">
      <Label htmlFor={field.name}>
        {field.label}
        {field.required && <span className="text-red-500 ml-1">*</span>}
      </Label>
      {renderField()}
      {field.description && (
        <p className="text-sm text-muted-foreground">{field.description}</p>
      )}
      {error && <p className="text-sm text-red-500">{error}</p>}
    </div>
  );
}
