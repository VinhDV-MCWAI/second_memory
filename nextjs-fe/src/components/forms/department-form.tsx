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
import type { DepartmentMst } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { DepartmentStatus, DepartmentStatusLabels } from '@/shared/enums';
import { getDepartmentSchema, type DepartmentFormData } from '@/shared/validation/validation';
import type { DepartmentFormProps } from './types';



export function DepartmentForm({ initialData, onSuccess, onCancel }: DepartmentFormProps) {
  const tCommon = useTranslations('common');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<DepartmentMst>(ENDPOINTS.MASTER.DEPARTMENT);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<DepartmentFormData>({
    resolver: zodResolver(getDepartmentSchema(tValidation)),
    defaultValues: {
      code: initialData?.code || '',
      name: initialData?.name || '',
      status: initialData ? Number(initialData.status) : DepartmentStatus.ACTIVE,
    },
  });

  useEffect(() => {
    if (initialData) {
      reset({
        code: initialData.code,
        name: initialData.name,
        status: Number(initialData.status),
      });
    } else {
      reset({
        code: '',
        name: '',
        status: DepartmentStatus.ACTIVE,
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: DepartmentFormData) => {
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
        console.error(error);
        handleBindErrors(error, setError);
      }
    });
  };

  // Use useWatch hook instead of watch() to avoid React Compiler issues
  const statusValue = useWatch({ control, name: 'status' });

  const FormContent = (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="code">
            {tLabels('code')} <span className="text-red-500">*</span>
          </Label>
          <Input
            id="code"
            {...register('code')}
            className={errors.code ? 'border-red-500' : ''}
            disabled={isEdit}
          />
          {errors.code && (
            <p className="text-sm text-red-500">{errors.code.message}</p>
          )}
        </div>

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
      </div>

      <div className="space-y-2">
        <Label htmlFor="status">
          {tLabels('status')} <span className="text-red-500">*</span>
        </Label>
        <Select
          value={statusValue?.toString()}
          onValueChange={(value) => setValue('status', Number(value))}
        >
          <SelectTrigger className={errors.status ? 'border-red-500' : ''}>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={DepartmentStatus.INACTIVE.toString()}>{DepartmentStatusLabels[DepartmentStatus.INACTIVE]}</SelectItem>
            <SelectItem value={DepartmentStatus.ACTIVE.toString()}>{DepartmentStatusLabels[DepartmentStatus.ACTIVE]}</SelectItem>
            <SelectItem value={DepartmentStatus.DRAFT.toString()}>{DepartmentStatusLabels[DepartmentStatus.DRAFT]}</SelectItem>
            <SelectItem value={DepartmentStatus.ARCHIVED.toString()}>{DepartmentStatusLabels[DepartmentStatus.ARCHIVED]}</SelectItem>
            {/* Fallback for other existing values if any */}
            {!Object.values(DepartmentStatus).includes(Number(statusValue)) && statusValue && (
              <SelectItem value={statusValue.toString()}>{statusValue}</SelectItem>
            )}
          </SelectContent>
        </Select>
        {errors.status && (
          <p className="text-sm text-red-500">{errors.status.message}</p>
        )}
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
      <TabsList className="grid w-full grid-cols-3">
        <TabsTrigger value="details">{tCommon('details')}</TabsTrigger>
        <TabsTrigger value="policy-departments">{tCommon('policyDepartments')}</TabsTrigger>
        <TabsTrigger value="history">{tCommon('history')}</TabsTrigger>
      </TabsList>
      
      <TabsContent value="details" className="mt-4">
        {FormContent}
      </TabsContent>

      <TabsContent value="policy-departments" className="mt-4">
        <p className="text-muted-foreground">{tCommon('junctionManagementComingSoon')}</p>
      </TabsContent>
      
      <TabsContent value="history" className="mt-4">
        <div className="h-[400px] overflow-y-auto pr-2">
          {initialData && (
            <HistoryViewer
              entityType="department"
              entityId={initialData.id}
              endpoint={`${ENDPOINTS.MASTER.DEPARTMENT}-hist`}
            />
          )}
        </div>
      </TabsContent>
    </Tabs>
  );
}
