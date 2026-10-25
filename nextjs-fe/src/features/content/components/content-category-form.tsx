'use client';

import { Controller, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { Input } from '@/components/ui/input';
import { FormField } from '@/components/common/form-field';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
import { useDialogFormState } from '@/components/common/use-dialog-form-state';
import { FORM_VALIDATION_MODE } from '@/shared/config';
import { HistoryViewer } from '@/features/history/components/history-viewer';
import { AttachmentsField } from '@/features/content/components/attachments-field';
import { CategoryTreeSelect } from '@/features/content/components/category-tree-select';
import { useCategoryTree } from '@/features/content/hooks/use-content-queries';
import { flattenTree, heightOf, selfAndDescendants } from '@/features/content/lib/category-tree';
import { IsPublicField } from '@/features/content/components/is-public-field';
import { MarkdownEditor } from '@/features/content/components/markdown-editor';
import { useCrud } from '@/shared/hooks/use-crud';
import { ValidationRules } from '@/shared/validation/validation-rules';
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

/**
 * A category of the tree (ADR-0022, at most CATEGORY_DEPTH_MAX levels since ADR-0023): no parent makes it a group. Its path (slug) is set from the name on
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
  const { data: categories } = useCategoryTree();
  // Not under itself or its own branch, and not where the branch would go deeper than allowed
  const branchHeight = initialData ? heightOf(categories, initialData.id) : 1;
  const ownBranch = initialData
    ? selfAndDescendants(categories, initialData.id)
    : new Set<number>();
  const blocked = new Set(
    flattenTree(categories)
      .filter(
        (node) =>
          ownBranch.has(node.row.id) ||
          node.level + 1 + branchHeight > ValidationRules.CATEGORY_DEPTH_MAX,
      )
      .map((node) => node.row.id),
  );

  const form = useForm<ContentCategoryFormData>({
    mode: FORM_VALIDATION_MODE,
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
  const {
    register,
    handleSubmit,
    control,
    setError,
    formState: { errors },
  } = form;

  useDialogFormState(form, {
    pending: loading,
    editing: !!initialData,
    onStateChange,
  });

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
          <FormField
            id="parent_id"
            label={tContent('parentCategory')}
            error={errors.parent_id?.message}
          >
            <Controller
              control={control}
              name="parent_id"
              render={({ field }) => (
                <CategoryTreeSelect
                  id="parent_id"
                  mode="single"
                  noneLabel={tContent('noGroup')}
                  categories={categories}
                  disabledIds={blocked}
                  value={field.value}
                  onChange={field.onChange}
                  invalid={!!errors.parent_id}
                />
              )}
            />
          </FormField>
        </div>
        <FormField
          id="description"
          label={tLabels('description')}
          error={errors.description?.message}
        >
          <Controller
            control={control}
            name="description"
            render={({ field }) => (
              <MarkdownEditor
                id="description"
                value={field.value}
                onChange={field.onChange}
                rows={6}
              />
            )}
          />
        </FormField>
        <p className="text-xs text-muted-foreground">{tContent('descriptionHint')}</p>
        <div className="space-y-2">
          <p className="text-sm font-medium">{tContent('attachments')}</p>
          <p className="text-xs text-muted-foreground">{tContent('attachmentsHint')}</p>
          <AttachmentsField control={control} register={register} initialFiles={initialFiles} />
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
