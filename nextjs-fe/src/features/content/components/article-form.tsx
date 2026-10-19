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
import { IsPublicField } from '@/features/content/components/is-public-field';
import { useApiData } from '@/shared/hooks/use-api-data';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { getArticleSchema, type ArticleFormData } from '@/shared/validation/validation';
import type { Article, ContentCategory } from '@/shared/types/models';

/** audit_log.auditable_type of articles (ContentConst::ARTICLE_AUDITABLE_TYPE). */
const AUDITABLE_TYPE = 'article';

/** A home page has a handful of categories; one page fills the select. */
const CATEGORY_PAGE_SIZE = 100;

/**
 * An article of the public site (ADR-0021). The body is Markdown; the public page shows it as
 * HTML (raw HTML is escaped). It is visible when it and its category are both public.
 */
export function ArticleForm({
  initialData,
  formId,
  onSuccess,
  onStateChange,
}: ResourceFormProps<Article>) {
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const tContent = useTranslations('content');
  // Category lists show how many articles each one has
  const { create, update, loading } = useCrud(API_ENDPOINTS.CONTENT.ARTICLE, {
    invalidateKeys: [API_ENDPOINTS.CONTENT.ARTICLE, API_ENDPOINTS.CONTENT.CATEGORY],
  });
  const { data: categories } = useApiData<ContentCategory>(API_ENDPOINTS.CONTENT.CATEGORY, {
    per_page: CATEGORY_PAGE_SIZE,
  });

  const {
    register,
    handleSubmit,
    control,
    setError,
    formState: { errors, isDirty },
  } = useForm<ArticleFormData>({
    resolver: zodResolver(getArticleSchema(tValidation)),
    values: {
      content_category_id: initialData?.content_category_id ?? 0,
      title: initialData?.title ?? '',
      summary: initialData?.summary ?? '',
      body: initialData?.body ?? '',
      sort_order: initialData?.sort_order ?? 0,
      is_public: initialData?.is_public ?? true,
    },
  });

  useEffect(
    () => onStateChange({ pending: loading, dirty: isDirty }),
    [loading, isDirty, onStateChange],
  );

  const onSubmit = async (data: ArticleFormData) => {
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
            <FormField
              id="content_category_id"
              label={tLabels('categoryOfArticle')}
              required
              error={errors.content_category_id?.message}
            >
              <Controller
                control={control}
                name="content_category_id"
                render={({ field }) => (
                  <OptionSelect
                    value={field.value || undefined}
                    onChange={(value) => field.onChange(Number(value))}
                    options={categories.map((category) => ({
                      value: category.id,
                      label: category.name,
                    }))}
                  />
                )}
              />
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
        <FormField id="title" label={tLabels('title')} required error={errors.title?.message}>
          <Input id="title" {...register('title')} aria-invalid={!!errors.title} />
        </FormField>
        <FormField id="summary" label={tLabels('summary')} error={errors.summary?.message}>
          <Textarea id="summary" rows={2} {...register('summary')} />
        </FormField>
        <FormField id="body" label={tLabels('body')} required error={errors.body?.message}>
          <Textarea
            id="body"
            rows={14}
            className="font-mono text-sm"
            {...register('body')}
            aria-invalid={!!errors.body}
          />
        </FormField>
        <p className="text-xs text-muted-foreground">{tContent('bodyHint')}</p>
        <IsPublicField control={control} />
        {initialData && (
          <a
            href={`/guides/${initialData.category_slug}/${initialData.slug}`}
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
