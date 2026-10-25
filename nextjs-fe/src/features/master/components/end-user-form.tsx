'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FormField } from '@/components/common/form-field';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
import { useDialogFormState } from '@/components/common/use-dialog-form-state';
import { FORM_VALIDATION_MODE } from '@/shared/config';
import { HistoryViewer } from '@/features/history/components/history-viewer';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { IsActive } from '@/shared/enums/enums';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { getEndUserSchema, type EndUserFormData } from '@/shared/validation/validation';
import type { EndUser } from '@/shared/types/models';

/** audit_log.auditable_type of end users (EndUserConst::AUDITABLE_TYPE). */
const AUDITABLE_TYPE = 'end_user';

/** End user (ADR-0020): edit only, they sign up on the public site. Lock / unlock, new password. */
export function EndUserForm({
  initialData,
  formId,
  onSuccess,
  onStateChange,
}: ResourceFormProps<EndUser>) {
  const tLabels = useTranslations('forms.labels');
  const tFields = useTranslations('fields');
  const tValidation = useTranslations('validation');
  const { update, loading } = useCrud(API_ENDPOINTS.MASTER.END_USER);

  const form = useForm<EndUserFormData>({
    mode: FORM_VALIDATION_MODE,
    resolver: zodResolver(getEndUserSchema(tValidation)),
    values: { is_active: initialData?.is_active ?? true, password: '' },
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

  const onSubmit = async (data: EndUserFormData) => {
    if (!initialData) return;
    try {
      await update(initialData.id, {
        is_active: data.is_active ? IsActive.TRUE : IsActive.FALSE,
        ...(data.password ? { password: data.password } : {}),
      });
      onSuccess();
    } catch (error: unknown) {
      handleBindErrors(error, setError);
    }
  };

  return (
    <>
      <form id={formId} onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <dl className="grid grid-cols-2 gap-2 text-sm">
          <dt className="text-muted-foreground">{tFields('username')}</dt>
          <dd>{initialData?.username}</dd>
          <dt className="text-muted-foreground">{tFields('displayName')}</dt>
          <dd>{initialData?.display_name}</dd>
        </dl>
        <div className="flex items-center gap-2">
          <input type="checkbox" id="is_active" {...register('is_active')} className="rounded" />
          <Label htmlFor="is_active">{tLabels('activeEndUser')}</Label>
        </div>
        <FormField id="password" label={tLabels('newPassword')} error={errors.password?.message}>
          <Input
            id="password"
            type="password"
            autoComplete="new-password"
            {...register('password')}
            aria-invalid={!!errors.password}
          />
          <p className="text-xs text-muted-foreground">{tLabels('newPasswordHint')}</p>
        </FormField>
      </form>
      {initialData && (
        <HistoryViewer auditableType={AUDITABLE_TYPE} recordId={initialData.id} className="mt-6" />
      )}
    </>
  );
}
