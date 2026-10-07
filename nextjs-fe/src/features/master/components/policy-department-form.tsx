'use client';

import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { useCrud } from '@/shared/hooks/use-crud';
import { useActionLock } from '@/shared/hooks/use-action-lock';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FormField } from '@/components/common/form-field';
import { ENDPOINTS } from '@/shared/api';
import {
  getPolicyDepartmentSchema,
  type PolicyDepartmentFormData,
} from '@/shared/validation/validation';
import type { PolicyDepartmentMst } from '@/shared/types/api';
import type { ResourceFormProps } from '@/components/common/resource-list-page';

export function PolicyDepartmentForm({
  initialData,
  onSuccess,
  onCancel,
}: ResourceFormProps<PolicyDepartmentMst>) {
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud(ENDPOINTS.MASTER.POLICY_DEPARTMENT);

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

  const { execute, isLoading: isActionProcessing } = useActionLock({
    delay: UI_CONSTANTS.ACTION_DELAY_MS,
  });

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
      <FormField
        id="table_name"
        label={tLabels('tableName')}
        required
        error={errors.table_name?.message}
      >
        <Input
          id="table_name"
          {...register('table_name')}
          className={errors.table_name ? 'border-red-500' : ''}
          placeholder={tForms('termsOfService')}
        />
      </FormField>

      <FormField id="row_id" label={tLabels('rowId')} required error={errors.row_id?.message}>
        <Input
          id="row_id"
          type="number"
          {...register('row_id')}
          className={errors.row_id ? 'border-red-500' : ''}
        />
      </FormField>

      <div className="flex justify-end gap-2 pt-4">
        <Button
          type="button"
          variant="outline"
          onClick={onCancel}
          disabled={loading || isActionProcessing}
        >
          {tCommon('cancel')}
        </Button>
        <Button type="submit" disabled={loading || isActionProcessing}>
          {loading || isActionProcessing
            ? isEdit
              ? tCommon('updating')
              : tCommon('creating')
            : isEdit
              ? tCommon('update')
              : tCommon('create')}
        </Button>
      </div>
    </form>
  );
}
