'use client';

import { useEffect } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { FormField } from '@/components/common/form-field';
import { OptionSelect } from '@/components/common/option-select';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
import { HistoryViewer } from '@/features/history/components/history-viewer';
import { AttachmentsField } from '@/features/content/components/attachments-field';
import { IsPublicField } from '@/features/content/components/is-public-field';
import { useApiData } from '@/shared/hooks/use-api-data';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { publicPaths } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import {
  getContentCategorySchema,
  type ContentCategoryFormData,
} from '@/shared/validation/validation';
import type { AttachedFile, ContentCategory } from '@/shared/types/models';

/** audit_log.auditable_type of categories (ContentConst::CATEGORY_AUDITABLE_TYPE). */
const AUDITABLE_TYPE = 'content_category';

/** A site has a handful of groups; one page fills the select. */
const GROUP_PAGE_SIZE = 100;

/** Value of the "no parent" option (Radix Select forbids an empty value). */
const NO_PARENT = 'none';

/**
 * A group (no parent) or a category inside one (ADR-0022). Its path (slug) is set from the name on
 * create and never changes. The description is Markdown; attachments are its evidence.
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
  // Articles show their categories' names
  const { create, update, loading } = useCrud(API_ENDPOINTS.CONTENT.CATEGORY, {
    invalidateKeys: [API_ENDPOINTS.CONTENT.CATEGORY, API_ENDPOINTS.CONTENT.ARTICLE],
  });
  const { data: groups } = useApiData<ContentCategory>(API_ENDPOINTS.CONTENT.CATEGORY, {
    per_page: GROUP_PAGE_SIZE,
    filters: { root: 1 },
  });
  // A group that holds categories stays a group (two levels at most)
  const mustStayGroup = (initialData?.children_count ?? 0) > 0;

  const {
    register,
    handleSubmit,
    control,
    setError,
    formState: { errors, isDirty },
  } = useForm<ContentCategoryFormData>({
    resolver: zodResolver(getContentCategorySchema(tValidation)),
    values: {
      parent_id: initialData?.parent_id ?? null,
      name: initialData?.name ?? '',
      description: initialData?.description ?? '',
      is_public: initialData?.is_public ?? true,
      attachments: (initialData?.attachments ?? []).map((attachment) => ({
        id: attachment.id,
        title: attachment.title,
        url: attachment.url ?? '',
        media_id: attachment.media_id,
        note: attachment.note ?? '',
      })),
    },
  });

  useEffect(
    () => onStateChange({ pending: loading, dirty: isDirty }),
    [loading, isDirty, onStateChange],
  );

  const onSubmit = async ({ attachments, ...data }: ContentCategoryFormData) => {
    const payload = {
      ...data,
      attachments: attachments.map(({ url, note, ...attachment }) => ({
        ...attachment,
        url: url || null,
        note: note || null,
      })),
    };
    try {
      if (initialData) {
        await update(initialData.id, payload);
      } else {
        await create(payload);
      }
      onSuccess();
    } catch (error: unknown) {
      handleBindErrors(error, setError);
    }
  };

  const groupOptions = [
    { value: NO_PARENT, label: tContent('noGroup') },
    ...groups
      .filter((group) => group.id !== initialData?.id)
      .map((group) => ({ value: group.id, label: group.name })),
  ];
  const initialFiles = (initialData?.attachments ?? [])
    .map((attachment) => attachment.file)
    .filter((file): file is AttachedFile => file !== null);

  return (
    <>
      <form id={formId} onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <FormField id="name" label={tLabels('name')} required error={errors.name?.message}>
            <Input id="name" {...register('name')} aria-invalid={!!errors.name} />
          </FormField>
          {!mustStayGroup && (
            <FormField id="parent_id" label={tContent('group')} error={errors.parent_id?.message}>
              <Controller
                control={control}
                name="parent_id"
                render={({ field }) => (
                  <OptionSelect
                    value={field.value ?? NO_PARENT}
                    onChange={(value) => field.onChange(value === NO_PARENT ? null : Number(value))}
                    options={groupOptions}
                  />
                )}
              />
            </FormField>
          )}
        </div>
        <FormField
          id="description"
          label={tLabels('description')}
          error={errors.description?.message}
        >
          <Textarea
            id="description"
            rows={6}
            className="font-mono text-sm"
            {...register('description')}
          />
        </FormField>
        <p className="text-xs text-muted-foreground">{tContent('descriptionHint')}</p>
        <div className="space-y-2">
          <p className="text-sm font-medium">{tContent('attachments')}</p>
          <p className="text-xs text-muted-foreground">{tContent('attachmentsHint')}</p>
          <AttachmentsField
            control={control}
            register={register}
            errors={errors}
            initialFiles={initialFiles}
          />
          {errors.attachments?.message && (
            <p className="text-sm text-destructive">{errors.attachments.message}</p>
          )}
        </div>
        <IsPublicField control={control} />
        {initialData && (
          <a
            href={publicPaths.category(initialData.slug)}
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
