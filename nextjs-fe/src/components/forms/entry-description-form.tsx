'use client';

import { useEffect, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import type { JSONContent } from '@tiptap/react';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { NovelEditor } from '@/components/features/editor/novel-editor';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { EntryDescriptionMgmt } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { FORM_DEFAULTS } from '@/shared/config/constant';
import { StatusEnum, StatusEnumLabels, IsActive } from '@/shared/enums';
import { getEntryDescriptionSchema, type EntryDescriptionFormData } from '@/shared/validation/validation';
import type { EntryDescriptionFormProps } from './types';

const getFormValues = (data: EntryDescriptionFormProps['initialData']): EntryDescriptionFormData => {
    if (data) {
      return {
        title: data.title,
        summary: data.summary || '',
        article: data.article || '',
        rank_order: Number(data.rank_order),
        status: Number(data.status) as StatusEnum,
        is_display: Boolean(data.is_display),
      };
    }
    return {
      title: '',
      summary: '',
      article: '',
      rank_order: FORM_DEFAULTS.RANK_ORDER,
      status: StatusEnum.PUBLISHED,
      is_display: true,
    };
  };

export function EntryDescriptionForm({ initialData, onSuccess, onCancel, hideActions = false }: EntryDescriptionFormProps) {
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<EntryDescriptionMgmt>(ENDPOINTS.MANAGEMENT.ENTRY_DESCRIPTION);
  
  const [articleContent, setArticleContent] = useState<JSONContent | null>(null);

  const defaultValues = getFormValues(initialData);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<EntryDescriptionFormData>({
    resolver: zodResolver(getEntryDescriptionSchema(tValidation)),
    defaultValues: defaultValues,
  });

  useEffect(() => {
    reset(getFormValues(initialData));
    // Initialize article content from initialData
    if (initialData?.article) {
      queueMicrotask(() => {
        try {
          const parsed = JSON.parse(initialData.article as string);
          setArticleContent(parsed);
        } catch {
          // If not JSON, create a simple doc with text
          setArticleContent({
            type: 'doc',
            content: [{ type: 'paragraph', content: [{ type: 'text', text: initialData.article }] }]
          });
        }
      });
    } else {
      queueMicrotask(() => setArticleContent(null));
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: EntryDescriptionFormData) => {
    await execute(async () => {
      try {
        // Convert form data to API payload format
        const payload = {
          title: data.title,
          summary: data.summary,
          status: Number(data.status),
          rank_order: Number(data.rank_order),
          is_display: data.is_display ? IsActive.TRUE : IsActive.FALSE,
          is_delete: 0,
          article: articleContent ? JSON.stringify(articleContent) : '',
        };
      
      if (isEdit && initialData) {
        await update(initialData.id, payload);
      } else {
        await create(payload);
      }
      onSuccess();
    } catch (error: unknown) {
      console.error(error);
      handleBindErrors(error, setError);
    }
    });
  };

  // Use useWatch hook instead of watch() to avoid React Compiler issues
  const statusValue = useWatch({ control, name: 'status' });

  return (
    <form onSubmit={handleSubmit(onSubmit)} id="entryDescriptionForm" className="space-y-4">

      <div className="space-y-2">
        <Label htmlFor="title">
          {tCommon('title')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="title"
          {...register('title')}
          className={errors.title ? 'border-red-500' : ''}
        />
        {errors.title && (
          <p className="text-sm text-red-500">{errors.title.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="summary">{tCommon('summary')}</Label>
        <Textarea
          id="summary"
          {...register('summary')}
          rows={3}
        />
      </div>

      <div className="space-y-2">
        <Label htmlFor="article">{tCommon('article')}</Label>
        <NovelEditor
          content={articleContent}
          onChange={(content) => {
            setArticleContent(content);
            setValue('article', JSON.stringify(content));
          }}
          placeholder="Write your article content..."
          className="min-h-[400px]"
        />
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="rank_order">
            {tCommon('displayOrder')} <span className="text-red-500">*</span>
          </Label>
          <Input
            id="rank_order"
            type="number"
            {...register('rank_order', { valueAsNumber: true })}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="status">
            {tCommon('status')} <span className="text-red-500">*</span>
          </Label>
          <Select
            value={statusValue !== undefined ? String(statusValue) : ''}
            onValueChange={(value) => setValue('status', Number(value) as StatusEnum)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={String(StatusEnum.PUBLISHED)}>{StatusEnumLabels[StatusEnum.PUBLISHED]}</SelectItem>
              <SelectItem value={String(StatusEnum.DRAFT)}>{StatusEnumLabels[StatusEnum.DRAFT]}</SelectItem>
              <SelectItem value={String(StatusEnum.ARCHIVED)}>{StatusEnumLabels[StatusEnum.ARCHIVED]}</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="flex items-center gap-2 mt-4">
        <input
          type="checkbox"
          id="is_display"
          {...register('is_display')}
          className="rounded"
        />
        <Label htmlFor="is_display">{tCommon('isDisplay')}</Label>
      </div>

      {!hideActions && (
        <div className="flex justify-end gap-2 pt-4">
          <Button type="button" variant="outline" onClick={onCancel} disabled={loading || isActionProcessing}>
            {tCommon('cancel')}
          </Button>
          <Button type="submit" disabled={loading || isActionProcessing}>
            {loading || isActionProcessing ? (isEdit ? tCommon('updating') : tCommon('creating')) : (isEdit ? tCommon('update') : tCommon('create'))}
          </Button>
        </div>
      )}
    </form>
  );
}
