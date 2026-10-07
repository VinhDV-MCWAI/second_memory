'use client';

import { useEffect, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { IsActive } from '@/shared/enums/enums';
import { useCrud } from '@/shared/hooks/use-crud';
import { useActionLock } from '@/shared/hooks/use-action-lock';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { formatDateForBackend, formatDateForInput } from '@/shared/utils/date-formatter';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FormField } from '@/components/common/form-field';
import { OptionSelect } from '@/components/common/option-select';
import { enumOptions } from '@/shared/utils/enum-options';
import { AvatarUpload } from '@/components/common/avatar-upload';
import type { AdminMst } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import {
  AdminRole,
  AdminRoleLabels,
  AdminStatus,
  Gender,
  GenderLabels,
  AdminStatusLabels,
} from '@/shared/enums';
import { UPLOAD_CONFIG } from '@/shared/config';
import { getAdminSchema, type AdminFormData } from '@/shared/validation/validation';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
import { HistoryViewer } from '@/features/history/components/history-viewer';

/** audit_log.auditable_type of admins (AdminMstService::$auditableType). */
const AUDITABLE_TYPE = 'admin';

const STATUS_OPTIONS = [
  AdminStatus.ACTIVE,
  AdminStatus.INACTIVE,
  AdminStatus.WAITING,
  AdminStatus.SUSPENDED,
].map((value) => ({ value, label: AdminStatusLabels[value] }));

export function AdminForm({ initialData, onSuccess, onCancel }: ResourceFormProps<AdminMst>) {
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud(ENDPOINTS.MASTER.ADMIN);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(
    () => initialData?.avatar ?? null,
  );

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<AdminFormData>({
    resolver: zodResolver(getAdminSchema(tValidation)),
    defaultValues: {
      gender: Gender.MALE,
      status: AdminStatus.ACTIVE,
      role: AdminRole.VIEWER,
      is_active: true,
    },
  });

  useEffect(() => {
    if (initialData) {
      reset({
        email: initialData.email,
        user_name: initialData.user_name,
        first_name: initialData.first_name,
        last_name: initialData.last_name,
        address: initialData.address || '',
        phone_number: initialData.phone_number || '',
        birth: formatDateForInput(initialData.birth),
        gender: initialData.gender ? Number(initialData.gender) : Gender.MALE,
        status: initialData.status !== undefined ? Number(initialData.status) : AdminStatus.ACTIVE,
        role: initialData.role as AdminRole,
        is_active: initialData.is_active,
        avatar: initialData.avatar,
      });
    } else {
      reset({
        email: '',
        user_name: '',
        password: '',
        first_name: '',
        last_name: '',
        address: '',
        phone_number: '',
        birth: '',
        gender: Gender.MALE,
        status: AdminStatus.ACTIVE,
        role: AdminRole.VIEWER,
        is_active: true,
        avatar: '',
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({
    delay: UI_CONSTANTS.ACTION_DELAY_MS,
  });

  const onSubmit = async (data: AdminFormData) => {
    await execute(async () => {
      if (!isEdit && !data.password) {
        setError('password', { type: 'manual', message: tCommon('passwordRequired') });
        return;
      }

      try {
        // Avatar upload is not implemented: only the preview is shown
        const payload = {
          ...data,
          is_active: data.is_active ? IsActive.TRUE : IsActive.FALSE,
        };

        if (data.birth) {
          payload.birth = formatDateForBackend(data.birth);
        }

        if (isEdit && initialData) {
          if (!payload.password) {
            delete payload.password;
          }
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

  // Use useWatch hook instead of watch() to avoid React Compiler issues
  const genderValue = useWatch({ control, name: 'gender' });
  const statusValue = useWatch({ control, name: 'status' });
  const roleValue = useWatch({ control, name: 'role' });

  return (
    <>
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <div className="mb-4 flex justify-center">
          <AvatarUpload
            value={avatarPreview ?? undefined}
            onChange={(_file, preview) => setAvatarPreview(preview)}
            maxSize={UPLOAD_CONFIG.DEFAULT_AVATAR_MAX_SIZE_MB}
          />
        </div>

        <div className="grid grid-cols-2 gap-4">
          <FormField
            id="first_name"
            label={tLabels('firstName')}
            required
            error={errors.first_name?.message}
          >
            <Input
              id="first_name"
              {...register('first_name')}
              className={errors.first_name ? 'border-red-500' : ''}
            />
          </FormField>
          <FormField
            id="last_name"
            label={tLabels('lastName')}
            required
            error={errors.last_name?.message}
          >
            <Input
              id="last_name"
              {...register('last_name')}
              className={errors.last_name ? 'border-red-500' : ''}
            />
          </FormField>
        </div>

        <div className="grid grid-cols-2 gap-4">
          <FormField id="email" label={tLabels('email')} required error={errors.email?.message}>
            <Input
              id="email"
              type="email"
              {...register('email')}
              className={errors.email ? 'border-red-500' : ''}
            />
          </FormField>
          <FormField
            id="user_name"
            label={tLabels('username')}
            required
            error={errors.user_name?.message}
          >
            <Input
              id="user_name"
              {...register('user_name')}
              className={errors.user_name ? 'border-red-500' : ''}
            />
          </FormField>
        </div>

        <div className="space-y-2">
          <Label htmlFor="password">
            {tLabels('password')}{' '}
            {isEdit ? (
              `(${tForms('leaveBlankToKeepCurrent')})`
            ) : (
              <span className="text-red-500">*</span>
            )}
          </Label>
          <Input
            id="password"
            type="password"
            {...register('password')}
            className={errors.password ? 'border-red-500' : ''}
            placeholder={isEdit ? tForms('passwordHidden') : tCommon('enterPassword')}
          />
          {errors.password && <p className="text-sm text-red-500">{errors.password.message}</p>}
        </div>

        <div className="grid grid-cols-2 gap-4">
          <FormField
            id="phone_number"
            label={tLabels('phoneNumber')}
            error={errors.phone_number?.message}
          >
            <Input
              id="phone_number"
              {...register('phone_number')}
              className={errors.phone_number ? 'border-red-500' : ''}
            />
          </FormField>
          <FormField id="birth" label={tLabels('birthDate')} error={errors.birth?.message}>
            <Input
              id="birth"
              type="date"
              {...register('birth')}
              className={errors.birth ? 'border-red-500' : ''}
            />
          </FormField>
        </div>

        <FormField id="address" label={tLabels('address')} error={errors.address?.message}>
          <Input
            id="address"
            {...register('address')}
            className={errors.address ? 'border-red-500' : ''}
          />
        </FormField>

        <div className="grid grid-cols-2 gap-4">
          <FormField id="gender" label={tLabels('gender')} required error={errors.gender?.message}>
            <OptionSelect
              value={genderValue}
              onChange={(value) => setValue('gender', Number(value) as Gender)}
              options={enumOptions(GenderLabels)}
            />
          </FormField>
          <FormField id="status" label={tLabels('status')} required error={errors.status?.message}>
            <OptionSelect
              value={statusValue}
              onChange={(value) => setValue('status', Number(value) as AdminStatus)}
              options={STATUS_OPTIONS}
            />
          </FormField>
        </div>

        <FormField id="role" label={tLabels('role')} required error={errors.role?.message}>
          <OptionSelect
            value={roleValue}
            onChange={(value) => setValue('role', value as AdminRole)}
            options={enumOptions(AdminRoleLabels)}
          />
        </FormField>

        <div className="mt-4 flex items-center gap-2">
          <input type="checkbox" id="is_active" {...register('is_active')} className="rounded" />
          <Label htmlFor="is_active">{tLabels('isActive')}</Label>
        </div>

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
      {isEdit && initialData && (
        <HistoryViewer auditableType={AUDITABLE_TYPE} recordId={initialData.id} className="mt-6" />
      )}
    </>
  );
}
