'use client';

import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { Input } from '@/components/ui/input';
import { FormField } from '@/components/common/form-field';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { getTagSchema, type TagFormData } from '@/shared/validation/validation';
import type { Tag } from '@/shared/types/models';

export function TagForm({ initialData, formId, onSuccess, onStateChange }: ResourceFormProps<Tag>) {
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  // Renaming a tag changes how skills show it
  const { create, update, loading } = useCrud(API_ENDPOINTS.LEDGER.TAG, {
    invalidateKeys: [API_ENDPOINTS.LEDGER.TAG, API_ENDPOINTS.LEDGER.SKILL],
  });

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isDirty },
  } = useForm<TagFormData>({
    resolver: zodResolver(getTagSchema(tValidation)),
    values: { name: initialData?.name ?? '' },
  });

  useEffect(
    () => onStateChange({ pending: loading, dirty: isDirty }),
    [loading, isDirty, onStateChange],
  );

  const onSubmit = async (data: TagFormData) => {
    try {
      if (initialData) {
        await update(initialData.id, data);
      } else {
        await create(data);
      }
      onSuccess();
    } catch (error: unknown) {
      handleBindErrors(error, setError);
    }
  };

  return (
    <form id={formId} onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <FormField id="name" label={tLabels('name')} required error={errors.name?.message}>
        <Input id="name" {...register('name')} aria-invalid={!!errors.name} />
      </FormField>
    </form>
  );
}
