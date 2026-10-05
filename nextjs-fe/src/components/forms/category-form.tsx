'use client';

import { useEffect, useState, useCallback } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { v4 as uuidv4 } from 'uuid';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { useApiData } from '@/shared/hooks/useApiData';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { CategoryMgmt, LayoutStructureItem, EntryMgmt } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { CategoryStatus, CategoryStatusLabels } from '@/shared/enums';
import { getCategorySchema, type CategoryFormData } from '@/shared/validation/validation';
import { slugify } from '@/shared/utils/string-utils';
import type { CategoryFormProps } from './types';
import { LayoutStructureEditor } from './layout-structure-editor';
import { IsActive } from '@/shared/enums/enums';

export function CategoryForm({ initialData, onSuccess, onCancel, renderActions = true, submitTriggerRef, hideActions = false }: CategoryFormProps) {
  
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<CategoryMgmt>(ENDPOINTS.MANAGEMENT.CATEGORY);
  const [activeTab, setActiveTab] = useState('details');
  const [layoutStructure, setLayoutStructure] = useState<LayoutStructureItem[]>([]);
  const [entrySearchQuery, setEntrySearchQuery] = useState('');

  const handleLayoutStructureChange = useCallback((newStructure: LayoutStructureItem[]) => {
    setLayoutStructure(newStructure);
  }, []);

  // Fetch available entries for layout structure
  const { data: availableEntries, loading: entriesLoading } = useApiData<EntryMgmt>(
    ENDPOINTS.MANAGEMENT.ENTRY,
    { 
      page: 1, 
      per_page: 100, 
      filters: entrySearchQuery ? { name: entrySearchQuery } : {} 
    }
  );

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<CategoryFormData>({
    // @ts-expect-error - z.preprocess causes status to be inferred as unknown
    resolver: zodResolver(getCategorySchema(tValidation)),
    defaultValues: {
      rank_order: 0,
      status: CategoryStatus.ACTIVE,
      is_display: true,
      slug: '',
      is_delete: false,
    },
  });

  useEffect(() => {
    if (initialData) {
      reset({
        name: initialData.name,
        description: initialData.description || '',
        slug: initialData.slug,
        rank_order: initialData.rank_order,
        status: initialData.status,
        is_display: initialData.is_display,
        is_delete: initialData.is_delete,
      });
      // Defer state update to avoid cascading renders
      queueMicrotask(() => {
        // Transform old schema (entry_id) to new schema (entry_mgmt_id) and add ui_id
        const transformedStructure = initialData.layout_structure?.map(item => {
          const legacyItem = item as { entry_id?: number };
          return {
            ui_id: item.ui_id || uuidv4(),
            entry_mgmt_id: item.entry_mgmt_id || legacyItem.entry_id,
            entry_desc_id: item.entry_desc_id,
            name: item.name,
            slug: item.slug,
            children: item.children,
          };
        }) || [];
        setLayoutStructure(transformedStructure);
      });
    } else {
      reset({
        name: '',
        description: '',
        slug: '',
        rank_order: 0,
        status: CategoryStatus.ACTIVE,
        is_display: true,
        is_delete: false,
      });
      // Defer state update to avoid cascading renders
      queueMicrotask(() => {
        setLayoutStructure([]);
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = useCallback(async (data: CategoryFormData) => {
    await execute(async () => {
      try {
        // Convert form data to API payload format
        const payload = {
          ...data,
          rank_order: Number(data.rank_order),
          status: Number(data.status),
          is_display: data.is_display ? IsActive.TRUE : IsActive.FALSE,
          layout_structure: layoutStructure,
        };
        
      if (isEdit && initialData) {
        await update(initialData.id, payload);
      } else {
        await create(payload);
      }
      onSuccess();
    } catch (error: unknown) {
      console.error('[CategoryForm] Submit error:', error);
      handleBindErrors(error, setError);
    }
    });
  }, [execute, isEdit, initialData, update, create, onSuccess, setError, layoutStructure]);

  // Expose submit function via ref (must be after onSubmit is defined)
  useEffect(() => {
    if (submitTriggerRef && typeof submitTriggerRef !== 'function') {
      submitTriggerRef.current = () => {
        
        // Call handleSubmit with both success and error handlers
        handleSubmit(
          (data) => {
            void onSubmit(data as unknown as CategoryFormData);
          },
          () => {
          }
        )();
      };
    }
    
    return () => {
      if (submitTriggerRef && typeof submitTriggerRef !== 'function') {
        submitTriggerRef.current = null;
      }
    };
  }, [submitTriggerRef, handleSubmit, onSubmit, errors, control]);

  // Use useWatch hook instead of watch() to avoid React Compiler issues
  const statusValue = useWatch({ control, name: 'status' });

  const FormFields = (
    <>
      <div className="space-y-2">
        <Label htmlFor="name">
          {tLabels('name')} <span className="text-red-500">*</span>
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
        <Label htmlFor="description">{tLabels('description')}</Label>
        <Textarea id="description" {...register('description')} rows={3} />
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="slug">{tLabels('slug')} <span className="text-red-500">*</span></Label>
          <Input
            id="slug"
            {...register('slug')}
            placeholder={tForms('slugExample')}
            className={errors.slug ? 'border-red-500' : ''}
          />
          {errors.slug && (
            <p className="text-sm text-red-500">{errors.slug.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="rank_order">
            {tLabels('displayOrder')} <span className="text-red-500">*</span>
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
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="status">
            {tLabels('status')} <span className="text-red-500">*</span>
          </Label>
          <Select
            value={statusValue?.toString()}
            onValueChange={(value) => setValue('status', Number(value) as CategoryStatus)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={CategoryStatus.ACTIVE.toString()}>{CategoryStatusLabels[CategoryStatus.ACTIVE]}</SelectItem>
              <SelectItem value={CategoryStatus.INACTIVE.toString()}>{CategoryStatusLabels[CategoryStatus.INACTIVE]}</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="flex items-center gap-2 mt-8">
          <input
            type="checkbox"
            id="is_display"
            {...register('is_display')}
            className="rounded"
          />
          <Label htmlFor="is_display">{tLabels('isDisplay')}</Label>
        </div>
      </div>
    </>
  );

  // For non-edit mode (create), wrap fields in form with buttons
  if (!isEdit) {
    return (
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      <form onSubmit={handleSubmit(onSubmit as any)} className="space-y-4">
        {FormFields}
        {!hideActions && (
          <div className="flex justify-end gap-2 pt-4">
            <Button type="button" variant="outline" onClick={onCancel} disabled={loading || isActionProcessing}>
              {tCommon('cancel')}
            </Button>
            <Button type="submit" disabled={loading || isActionProcessing}>
              {loading || isActionProcessing ? tCommon('creating') : tCommon('create')}
            </Button>
          </div>
        )}
      </form>
    );
  }

  // For edit mode, show tabs with fields and layout structure  
  
  return (
    <div className="flex flex-col">
      <Tabs key={`category-tabs-${initialData?.id || 'new'}`} value={activeTab} onValueChange={setActiveTab} className="w-full flex flex-col">
        <TabsList className="grid w-full grid-cols-2 mb-4">
          <TabsTrigger value="details" type="button">{tCommon('details')}</TabsTrigger>
          <TabsTrigger value="entries" type="button">{tCommon('entries')}</TabsTrigger>
        </TabsList>
        
        <TabsContent value="details" className="space-y-4 m-0">
          {FormFields}
        </TabsContent>

        <TabsContent value="entries" className="m-0">
          <div className="space-y-4">
            <div className="text-sm text-muted-foreground mb-4">
              Manage the layout structure for entries in this category. Drag and drop to reorder or change hierarchy.
            </div>
            <LayoutStructureEditor
              type="entry"
              value={layoutStructure}
              onChange={handleLayoutStructureChange}
              availableItems={availableEntries || []}
              loading={entriesLoading}
              onSearch={setEntrySearchQuery}
            />
          </div>
        </TabsContent>
      </Tabs>
      
      {/* Action buttons - only render if renderActions is true and hideActions is false */}
      {renderActions && !hideActions && (
        <div className="flex justify-end gap-2 pt-4 border-t mt-4">
          <Button type="button" variant="outline" onClick={onCancel} disabled={loading || isActionProcessing}>
            {tCommon('cancel')}
          </Button>
          <Button
            type="button"
            onClick={() => {
              // eslint-disable-next-line @typescript-eslint/no-explicit-any
              handleSubmit(onSubmit as any)();
            }}
            disabled={loading || isActionProcessing}
          >
            {loading || isActionProcessing ? tCommon('updating') : tCommon('update')}
          </Button>
        </div>
      )}
    </div>
  );
}
