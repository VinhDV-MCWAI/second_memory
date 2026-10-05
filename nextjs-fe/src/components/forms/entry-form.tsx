'use client';

import { useEffect, useState, useMemo } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { IsActive } from '@/shared/enums/enums';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { useApiData } from '@/shared/hooks/useApiData';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { HistoryViewer } from '@/components/features/history/history-viewer';
import type { EntryMgmt, LayoutStructureItem, EntryDescriptionMgmt } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { FORM_DEFAULTS } from '@/shared/config/constant';
import { EntryStatus, EntryStatusLabels } from '@/shared/enums';
import { getEntrySchema, type EntryFormData } from '@/shared/validation/validation';
import { slugify } from '@/shared/utils/string-utils';
import type { EntryFormProps } from './types';
import { LayoutStructureEditor } from './layout-structure-editor';

export function EntryForm({ initialData, onSuccess, onCancel, hideActions = false }: EntryFormProps) {
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<EntryMgmt>(ENDPOINTS.MANAGEMENT.ENTRY);
  const [activeTab, setActiveTab] = useState('details');
  const [layoutStructure, setLayoutStructure] = useState<LayoutStructureItem[]>([]);
  const [descSearchQuery, setDescSearchQuery] = useState('');

  // Fetch available entry descriptions for layout structure
  const { data: availableDescriptions, loading: descriptionsLoading } = useApiData<EntryDescriptionMgmt>(
    ENDPOINTS.MANAGEMENT.ENTRY_DESCRIPTION,
    { 
      page: 1, 
      per_page: 100, 
      filters: descSearchQuery ? { title: descSearchQuery } : {} 
    }
  );

  // Map descriptions to match LayoutStructureEditor's expected format
  const mappedDescriptions = useMemo(() => {
    return (availableDescriptions || []).map(desc => ({
      id: desc.id,
      name: desc.title,
    }));
  }, [availableDescriptions]);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<EntryFormData>({
    resolver: zodResolver(getEntrySchema(tValidation)) as any,
    defaultValues: {
      rank_order: FORM_DEFAULTS.RANK_ORDER,
      status: EntryStatus.ACTIVE,
      is_display: true,
    },
  });

  useEffect(() => {
    if (initialData) {
      reset({
        name: initialData.name,
        slug: initialData.slug || '',
        rank_order: initialData.rank_order,
        status: initialData.status,
        is_display: initialData.is_display ?? true,
      });
      queueMicrotask(() => {
        setLayoutStructure(initialData.layout_structure || []);
      });
    } else {
      reset({
        name: '',
        slug: '',
        rank_order: FORM_DEFAULTS.RANK_ORDER,
        status: EntryStatus.ACTIVE,
        is_display: true,
      });
      queueMicrotask(() => {
        setLayoutStructure([]);
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: EntryFormData) => {
    await execute(async () => {
      try {
        const payload = {
          name: data.name,
          slug: data.slug || '',
          rank_order: Number(data.rank_order),
          status: Number(data.status),
          is_display: data.is_display ? IsActive.TRUE : IsActive.FALSE,
          is_delete: false,
          layout_structure: layoutStructure,
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

  const FormContent = (
    <>
      <div className="space-y-2">
        <Label htmlFor="name">
          {tCommon('name')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="name"
          {...register('name', {
            onChange: (e) => {
              setValue('slug', slugify(e.target.value), { shouldValidate: true });
            },
          })}
          className={errors.name ? 'border-red-500' : ''}
        />
        {errors.name && (
          <p className="text-sm text-red-500">{errors.name.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="slug">{tCommon('slug')}</Label>
        <Input id="slug" {...register('slug')} placeholder={tForms('slugExample')} />
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
            className={errors.rank_order ? 'border-red-500' : ''}
          />
          {errors.rank_order && (
            <p className="text-sm text-red-500">{errors.rank_order.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="status">
            {tCommon('status')} <span className="text-red-500">*</span>
          </Label>
          <Select
            value={statusValue?.toString()}
            onValueChange={(value) => setValue('status', Number(value) as EntryStatus)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={EntryStatus.ACTIVE.toString()}>{EntryStatusLabels[EntryStatus.ACTIVE]}</SelectItem>
              <SelectItem value={EntryStatus.INACTIVE.toString()}>{EntryStatusLabels[EntryStatus.INACTIVE]}</SelectItem>
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
    </>
  );

  if (!isEdit) {
    return (
      <form onSubmit={handleSubmit(onSubmit)} id="entryForm" className="space-y-4">
        {FormContent}
      </form>
    );
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} id="entryForm" className="space-y-4">
      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="details">{tCommon('details')}</TabsTrigger>
          <TabsTrigger value="descriptions">{tCommon('descriptions')}</TabsTrigger>
          <TabsTrigger value="history">{tCommon('history')}</TabsTrigger>
        </TabsList>
        <TabsContent value="details" className="mt-4">
          <div className="space-y-4">
            {FormContent}
          </div>
        </TabsContent>
        <TabsContent value="descriptions" className="mt-4">
          <div className="space-y-4">
            <div className="text-sm text-muted-foreground mb-4">
              Manage the layout structure for descriptions in this entry. Drag and drop to reorder or change hierarchy.
            </div>
            <LayoutStructureEditor
              type="entry_desc"
              value={layoutStructure}
              onChange={setLayoutStructure}
              availableItems={mappedDescriptions}
              loading={descriptionsLoading}
              onSearch={setDescSearchQuery}
            />
          </div>
        </TabsContent>
        <TabsContent value="history" className="mt-4">
          <div className="h-[400px] overflow-y-auto pr-2">
            {initialData && (
              <HistoryViewer
                entityType="entry"
                entityId={initialData.id}
                endpoint={`${ENDPOINTS.MANAGEMENT.ENTRY}-hist`}
              />
            )}
          </div>
        </TabsContent>
      </Tabs>
      {!hideActions && (
        <div className="flex justify-end gap-2 pt-4 border-t">
          <Button type="button" variant="outline" onClick={onCancel} disabled={loading || isActionProcessing}>
            {tCommon('cancel')}
          </Button>
          <Button 
            type="submit" 
            disabled={loading || isActionProcessing}
          >
            {loading || isActionProcessing ? tCommon('updating') : tCommon('update')}
          </Button>
        </div>
      )}
    </form>
  );
}
