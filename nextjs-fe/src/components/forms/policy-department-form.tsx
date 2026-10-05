'use client';

import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PolicyDepartmentMst } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { getPolicyDepartmentSchema, type PolicyDepartmentFormData } from '@/shared/validation/validation';
import type { PolicyDepartmentFormProps } from './types';

export function PolicyDepartmentForm({ initialData, onSuccess, onCancel }: PolicyDepartmentFormProps) {
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<PolicyDepartmentMst>(ENDPOINTS.MASTER.POLICY_DEPARTMENT);

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
    setError,
  } = useForm<PolicyDepartmentFormData>({
    resolver: zodResolver(getPolicyDepartmentSchema(tValidation)),
    defaultValues: {},
  });

  useEffect(() => {
    if (initialData) {
      reset({
        table_name: initialData.table_name,
        row_id: initialData.row_id,
      });
    } else {
      reset({
        table_name: '',
        row_id: 0,
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: PolicyDepartmentFormData) => {
    await execute(async () => {
      try {
        // Convert string to number for row_id
        const payload = {
        ...data,
        row_id: Number(data.row_id),
      };
      
      if (isEdit && initialData) {
        await update(initialData.id, payload);
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

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="table_name">
          {tLabels('tableName')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="table_name"
          {...register('table_name')}
          className={errors.table_name ? 'border-red-500' : ''}
          placeholder={tForms('termsOfService')}
        />
        {errors.table_name && (
          <p className="text-sm text-red-500">{errors.table_name.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="row_id">
          {tLabels('rowId')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="row_id"
          type="number"
          {...register('row_id')}
          className={errors.row_id ? 'border-red-500' : ''}
        />
        {errors.row_id && (
          <p className="text-sm text-red-500">{errors.row_id.message}</p>
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
}
