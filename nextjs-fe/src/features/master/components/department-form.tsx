'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FormField } from '@/components/common/form-field';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
import { useDialogFormState } from '@/components/common/use-dialog-form-state';
import { FORM_VALIDATION_MODE } from '@/shared/config';
import { HistoryViewer } from '@/features/history/components/history-viewer';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { IsActive } from '@/shared/enums/enums';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { getDepartmentSchema, type DepartmentFormData } from '@/shared/validation/validation';
import type { Department } from '@/shared/types/models';

/** audit_log.auditable_type of departments (DepartmentService::$auditableType). */
const AUDITABLE_TYPE = 'department';

/** Department (ADR-0017). Members are picked in the admin form. */
export function DepartmentForm({
  initialData,
  formId,
  onSuccess,
  onStateChange,
}: ResourceFormProps<Department>) {
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  // The admin list shows department names
  const { create, update, loading } = useCrud(API_ENDPOINTS.MASTER.DEPARTMENT, {
    invalidateKeys: [API_ENDPOINTS.MASTER.DEPARTMENT, API_ENDPOINTS.MASTER.ADMIN],
  });

  const form = useForm<DepartmentFormData>({
    mode: FORM_VALIDATION_MODE,
    resolver: zodResolver(getDepartmentSchema(tValidation)),
    values: {
      code: initialData?.code ?? '',
      name: initialData?.name ?? '',
      description: initialData?.description ?? '',
      is_active: initialData?.is_active ?? true,
    },
  });
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = form;

  useDialogFormState(form, {
    pending: loading,
    editing: !!initialData,
    onStateChange,
  });

  const onSubmit = async (data: DepartmentFormData) => {
    const payload = {
      ...data,
      description: data.description || null,
      is_active: data.is_active ? IsActive.TRUE : IsActive.FALSE,
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

  return (
    <>
      <form id={formId} onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <FormField id="code" label={tLabels('code')} required error={errors.code?.message}>
            <Input id="code" {...register('code')} aria-invalid={!!errors.code} />
          </FormField>
          <FormField id="name" label={tLabels('name')} required error={errors.name?.message}>
            <Input id="name" {...register('name')} aria-invalid={!!errors.name} />
          </FormField>
        </div>
        <FormField
          id="description"
          label={tLabels('description')}
          error={errors.description?.message}
        >
          <Textarea id="description" rows={3} {...register('description')} />
        </FormField>
        <div className="flex items-center gap-2">
          <input type="checkbox" id="is_active" {...register('is_active')} className="rounded" />
          <Label htmlFor="is_active">{tLabels('isActive')}</Label>
        </div>
      </form>
      {initialData && (
        <HistoryViewer auditableType={AUDITABLE_TYPE} recordId={initialData.id} className="mt-6" />
      )}
    </>
  );
}
