'use client';

import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { Input } from '@/components/ui/input';
import { FormField } from '@/components/common/form-field';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
import { useDialogFormState } from '@/components/common/use-dialog-form-state';
import { FORM_VALIDATION_MODE } from '@/shared/config';
import { HistoryViewer } from '@/features/history/components/history-viewer';
import { PermissionMatrix } from '@/features/master/components/permission-matrix';
import { usePermissionGroups } from '@/features/master/hooks/use-permission-groups';
import { API_ENDPOINTS } from '@/shared/api';
import { AdminRole } from '@/shared/enums';
import { useCrud } from '@/shared/hooks/use-crud';
import type { Role } from '@/shared/types/models';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { getRoleSchema, type RoleFormData } from '@/shared/validation/validation';

/** audit_log.auditable_type of roles (RoleService::$auditableType). */
const AUDITABLE_TYPE = 'role';

export function RoleForm({
  initialData,
  formId,
  onSuccess,
  onStateChange,
}: ResourceFormProps<Role>) {
  const tLabels = useTranslations('forms.labels');
  const tPermissions = useTranslations('permissions');
  const tValidation = useTranslations('validation');
  const { data: groups = [] } = usePermissionGroups();
  // A role change also changes what its admins may do
  const { create, update, loading } = useCrud(API_ENDPOINTS.MASTER.ROLE, {
    invalidateKeys: [API_ENDPOINTS.MASTER.ROLE, API_ENDPOINTS.MASTER.ADMIN],
  });
  const isOwner = initialData?.name === AdminRole.OWNER;

  const form = useForm<RoleFormData>({
    mode: FORM_VALIDATION_MODE,
    resolver: zodResolver(getRoleSchema(tValidation)),
    // Kept sorted so ticking a box off and on again is not a change
    values: {
      name: initialData?.name ?? '',
      permissions: [...(initialData?.permissions ?? [])].sort(),
    },
  });
  const {
    register,
    handleSubmit,
    setError,
    setValue,
    control,
    formState: { errors },
  } = form;
  const permissions = useWatch({ control, name: 'permissions' });

  useDialogFormState(form, {
    pending: loading,
    editing: !!initialData,
    onStateChange,
    blocked: isOwner,
  });

  const onSubmit = async (data: RoleFormData) => {
    try {
      if (initialData) {
        // The owner role always holds every permission: the API refuses a list for it
        await update(initialData.id, isOwner ? { name: data.name } : data);
      } else {
        await create(data);
      }
      onSuccess();
    } catch (error: unknown) {
      handleBindErrors(error, setError);
    }
  };

  return (
    <>
      <form id={formId} onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <FormField id="name" label={tLabels('name')} required error={errors.name?.message}>
          <Input
            id="name"
            {...register('name')}
            disabled={initialData?.is_built_in}
            aria-invalid={!!errors.name}
          />
        </FormField>
        {initialData?.is_built_in && (
          <p className="text-sm text-muted-foreground">{tPermissions('builtInName')}</p>
        )}

        <FormField
          id="permissions"
          label={tLabels('permissions')}
          error={errors.permissions?.message}
        >
          {isOwner ? (
            <p className="text-sm text-muted-foreground">{tPermissions('ownerHasAll')}</p>
          ) : (
            <PermissionMatrix
              groups={groups}
              value={permissions}
              onChange={(next) => setValue('permissions', [...next].sort(), { shouldDirty: true })}
            />
          )}
        </FormField>
      </form>
      {initialData && (
        <HistoryViewer auditableType={AUDITABLE_TYPE} recordId={initialData.id} className="mt-6" />
      )}
    </>
  );
}
