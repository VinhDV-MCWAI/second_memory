'use client';

import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FormField } from '@/components/common/form-field';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
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

export function RoleForm({ initialData, onSuccess, onCancel }: ResourceFormProps<Role>) {
  const tCommon = useTranslations('common');
  const tLabels = useTranslations('forms.labels');
  const tPermissions = useTranslations('permissions');
  const tValidation = useTranslations('validation');
  const { data: groups = [] } = usePermissionGroups();
  // A role change also changes what its admins may do
  const { create, update, loading } = useCrud(API_ENDPOINTS.MASTER.ROLE, {
    invalidateKeys: [API_ENDPOINTS.MASTER.ROLE, API_ENDPOINTS.MASTER.ADMIN],
  });
  const isOwner = initialData?.name === AdminRole.OWNER;

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    control,
    formState: { errors },
  } = useForm<RoleFormData>({
    resolver: zodResolver(getRoleSchema(tValidation)),
    values: { name: initialData?.name ?? '', permissions: initialData?.permissions ?? [] },
  });
  const permissions = useWatch({ control, name: 'permissions' });

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
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
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
              onChange={(next) => setValue('permissions', next, { shouldDirty: true })}
            />
          )}
        </FormField>

        <div className="flex justify-end gap-2 pt-4">
          <Button type="button" variant="outline" onClick={onCancel} disabled={loading}>
            {tCommon('cancel')}
          </Button>
          <Button type="submit" disabled={loading || isOwner}>
            {initialData ? tCommon('update') : tCommon('create')}
          </Button>
        </div>
      </form>
      {initialData && (
        <HistoryViewer auditableType={AUDITABLE_TYPE} recordId={initialData.id} className="mt-6" />
      )}
    </>
  );
}
