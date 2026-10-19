'use client';

import { Controller, type Control, type FieldValues, type Path } from 'react-hook-form';
import { useTranslations } from 'next-intl';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

/** "Public" tick box of the category and article forms. */
export function IsPublicField<T extends FieldValues>({ control }: { control: Control<T> }) {
  const tLabels = useTranslations('forms.labels');

  return (
    <Controller
      control={control}
      name={'is_public' as Path<T>}
      render={({ field }) => (
        <div className="flex items-center gap-2">
          <Checkbox
            id="is_public"
            checked={field.value}
            onCheckedChange={(checked) => field.onChange(checked === true)}
          />
          <Label htmlFor="is_public">{tLabels('isPublic')}</Label>
        </div>
      )}
    />
  );
}
