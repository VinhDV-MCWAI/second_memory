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
import { CategoryTreeSelect } from '@/features/content/components/category-tree-select';
import { IsPublicField } from '@/features/content/components/is-public-field';
import { useCategoryTree } from '@/features/content/hooks/use-content-queries';
import { MarkdownEditor } from '@/features/content/components/markdown-editor';
import { SubPages } from '@/features/content/components/sub-pages';
import { useApiData } from '@/shared/hooks/use-api-data';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { publicPaths } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { getArticleSchema, type ArticleFormData } from '@/shared/validation/validation';
import type { Article } from '@/shared/types/models';

/** audit_log.auditable_type of articles (ContentConst::ARTICLE_AUDITABLE_TYPE). */
const AUDITABLE_TYPE = 'article';

/** Parents offered in the select: a personal site has at most a few hundred articles. */
const PARENT_PAGE_SIZE = 500;

/** Value of the "no parent" option (Radix Select forbids an empty value). */
const NO_PARENT = 'none';

/**
 * An article (ADR-0022): Markdown, any number of categories, optionally a sub-page of another
 * article (a book). It is visible when it and every article above it are public.
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
  const { data: categories } = useCategoryTree();
  const { data: articles } = useApiData<Article>(API_ENDPOINTS.CONTENT.ARTICLE, {
    per_page: PARENT_PAGE_SIZE,
    sort_by: 'title',
    sort_order: 'asc',
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
      parent_id: initialData?.parent_id ?? null,
      category_ids: initialData?.categories.map((category) => category.id) ?? [],
      title: initialData?.title ?? '',
      summary: initialData?.summary ?? '',
      body: initialData?.body ?? '',
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
        <FormField id="title" label={tLabels('title')} required error={errors.title?.message}>
          <Input id="title" {...register('title')} aria-invalid={!!errors.title} />
        </FormField>
        <FormField
          id="category_ids"
          label={tLabels('categories')}
          error={errors.category_ids?.message}
        >
          <Controller
            control={control}
            name="category_ids"
            render={({ field }) => (
              <CategoryTreeSelect
                id="category_ids"
                mode="multiple"
                categories={categories}
                value={field.value}
                onChange={field.onChange}
                invalid={!!errors.category_ids}
              />
            )}
          />
        </FormField>
        <FormField id="parent_id" label={tContent('parent')} error={errors.parent_id?.message}>
          <Controller
            control={control}
            name="parent_id"
            render={({ field }) => (
              <OptionSelect
                id="parent_id"
                value={field.value ?? NO_PARENT}
                onChange={(value) => field.onChange(value === NO_PARENT ? null : Number(value))}
                options={[
                  { value: NO_PARENT, label: tContent('noParent') },
                  ...articles
                    .filter((article) => article.id !== initialData?.id)
                    .map((article) => ({ value: article.id, label: article.title })),
                ]}
              />
            )}
          />
        </FormField>
        <FormField id="summary" label={tLabels('summary')} error={errors.summary?.message}>
          <Textarea id="summary" rows={2} {...register('summary')} />
        </FormField>
        <FormField id="body" label={tLabels('body')} required error={errors.body?.message}>
          <Controller
            control={control}
            name="body"
            render={({ field }) => (
              <MarkdownEditor
                id="body"
                value={field.value}
                onChange={field.onChange}
                rows={16}
                invalid={!!errors.body}
                articleId={initialData?.id}
              />
            )}
          />
        </FormField>
        <IsPublicField control={control} />
        {initialData && (
          <a
            href={publicPaths.article(initialData.slug)}
            target="_blank"
            rel="noreferrer"
            className="text-sm underline"
          >
            {tContent('viewOnSite')}
          </a>
        )}
      </form>
      {initialData && (
        <div className="mt-6">
          <SubPages articleId={initialData.id} />
        </div>
      )}
      {initialData && (
        <HistoryViewer auditableType={AUDITABLE_TYPE} recordId={initialData.id} className="mt-6" />
      )}
    </>
  );
}
