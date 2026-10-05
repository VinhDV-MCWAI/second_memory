'use client';

import { useEffect } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
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
import type { FeatureMst } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { FeatureStatus, FeatureStatusLabels } from '@/shared/enums';
import { getFeatureSchema, type FeatureFormData } from '@/shared/validation/validation';
import type { FeatureFormProps } from './types';

export function FeatureForm({ initialData, onSuccess, onCancel }: FeatureFormProps) {
  const tCommon = useTranslations('common');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<FeatureMst>(ENDPOINTS.MASTER.FEATURE);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<FeatureFormData>({
    resolver: zodResolver(getFeatureSchema(tValidation)),
    defaultValues: {
      status: FeatureStatus.ACTIVE,
    },
  });

  useEffect(() => {
    if (initialData) {
      reset({
        name: initialData.name,
        group_name: initialData.group_name || '',
        status: Number(initialData.status),
      });
    } else {
      reset({
        name: '',
        group_name: '',
        status: FeatureStatus.ACTIVE,
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: FeatureFormData) => {
    await execute(async () => {
      try {
        const payload = { ...data };

      if (isEdit && initialData) {
        if (!initialData) return;
        await update(initialData.id, {
          ...payload,
          is_delete: initialData.is_delete || false,
        });
      } else {
        await create({
          ...payload,
          is_delete: false,
        });
      }
      onSuccess();
    } catch (error: unknown) {
      // Error is handled by useCrud toast
      console.error(error);
      handleBindErrors(error, setError);
    }
    });
  };

  // Use useWatch hook instead of watch() to avoid React Compiler issues
  const statusValue = useWatch({ control, name: 'status' });

  const FormContent = (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="name">
          {tLabels('name')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="name"
          {...register('name')}
          className={errors.name ? 'border-red-500' : ''}
        />
        {errors.name && (
          <p className="text-sm text-red-500">{errors.name.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="group_name">
          {tLabels('groupName')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="group_name"
          {...register('group_name')}
          className={errors.group_name ? 'border-red-500' : ''}
        />
        {errors.group_name && (
          <p className="text-sm text-red-500">{errors.group_name.message}</p>
        )}
      </div>



      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="status">
            {tLabels('status')} <span className="text-red-500">*</span>
          </Label>
          <Select
            value={statusValue?.toString()}
            onValueChange={(value) => setValue('status', Number(value) as FeatureStatus)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={FeatureStatus.ACTIVE.toString()}>{FeatureStatusLabels[FeatureStatus.ACTIVE]}</SelectItem>
              <SelectItem value={FeatureStatus.INACTIVE.toString()}>{FeatureStatusLabels[FeatureStatus.INACTIVE]}</SelectItem>
            </SelectContent>
          </Select>
        </div>

      </div>

      <div className="flex justify-end gap-2 pt-4">
        <Button type="button" variant="outline" onClick={onCancel} disabled={loading || isActionProcessing}>
          {tCommon('cancel')}
        </Button>
        <Button type="submit" disabled={loading || isActionProcessing}>
          {loading || isActionProcessing ? (isEdit ? tCommon('updating') : tCommon('creating')) : (isEdit ? tCommon('update') : tCommon('create'))}
        </Button>
      </div>
    </form>
  );

  if (!isEdit) {
    return FormContent;
  }

  return (
    <Tabs defaultValue="details" className="w-full">
      <TabsList className="grid w-full grid-cols-2">
        <TabsTrigger value="details">{tCommon('details')}</TabsTrigger>
        <TabsTrigger value="history">{tCommon('history')}</TabsTrigger>
      </TabsList>
      <TabsContent value="details" className="mt-4">
        {FormContent}
      </TabsContent>
      <TabsContent value="history" className="mt-4">
        <div className="h-[400px] overflow-y-auto pr-2">
          {initialData && (
            <HistoryViewer
              entityType="feature"
              entityId={initialData.id}
              endpoint={`${ENDPOINTS.MASTER.FEATURE}-hist`}
            />
          )}
        </div>
      </TabsContent>
    </Tabs>
  );
}
