'use client';

import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { FormField } from '@/components/common/form-field';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
import { HistoryViewer } from '@/features/history/components/history-viewer';
import { IsPublicField } from '@/features/content/components/is-public-field';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { handleBindErrors } from '@/shared/utils/error-handler';
import {
  getContentCategorySchema,
  type ContentCategoryFormData,
} from '@/shared/validation/validation';
import type { ContentCategory } from '@/shared/types/models';

/** audit_log.auditable_type of categories (ContentConst::CATEGORY_AUDITABLE_TYPE). */
const AUDITABLE_TYPE = 'content_category';

/**
 * A category of the public home page (ADR-0021). Its path (slug) is set from the name on create
 * and never changes, so links to it keep working.
 */
export function ContentCategoryForm({
  initialData,
  formId,
  onSuccess,
  onStateChange,
}: ResourceFormProps<ContentCategory>) {
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const tContent = useTranslations('content');
  // Articles show their category's name
  const { create, update, loading } = useCrud(API_ENDPOINTS.CONTENT.CATEGORY, {
    invalidateKeys: [API_ENDPOINTS.CONTENT.CATEGORY, API_ENDPOINTS.CONTENT.ARTICLE],
  });

  const {
    register,
    handleSubmit,
    control,
    setError,
    formState: { errors, isDirty },
  } = useForm<ContentCategoryFormData>({
    resolver: zodResolver(getContentCategorySchema(tValidation)),
    values: {
      name: initialData?.name ?? '',
      description: initialData?.description ?? '',
      sort_order: initialData?.sort_order ?? 0,
      is_public: initialData?.is_public ?? true,
    },
  });

  useEffect(
    () => onStateChange({ pending: loading, dirty: isDirty }),
    [loading, isDirty, onStateChange],
  );

  const onSubmit = async (data: ContentCategoryFormData) => {
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
    <>
      <form id={formId} onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <div className="grid grid-cols-3 gap-4">
          <div className="col-span-2">
            <FormField id="name" label={tLabels('name')} required error={errors.name?.message}>
              <Input id="name" {...register('name')} aria-invalid={!!errors.name} />
            </FormField>
          </div>
          <FormField
            id="sort_order"
            label={tLabels('sortOrder')}
            error={errors.sort_order?.message}
          >
            <Input
              id="sort_order"
              type="number"
              min={0}
              {...register('sort_order', { valueAsNumber: true })}
            />
          </FormField>
        </div>
        <FormField
          id="description"
          label={tLabels('description')}
          error={errors.description?.message}
        >
          <Textarea id="description" rows={2} {...register('description')} />
        </FormField>
        <IsPublicField control={control} />
        {initialData && (
          <a
            href={`/guides/${initialData.slug}`}
            target="_blank"
            rel="noreferrer"
            className="text-sm underline"
          >
            {tContent('viewOnSite')}
          </a>
        )}
      </form>
      {initialData && (
        <HistoryViewer auditableType={AUDITABLE_TYPE} recordId={initialData.id} className="mt-6" />
      )}
    </>
  );
}
