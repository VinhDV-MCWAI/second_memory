'use client';

import { useEffect, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { useQueryClient } from '@tanstack/react-query';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { IsActive } from '@/shared/enums/enums';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { formatDateForBackend, formatDateForInput } from '@/shared/utils/date-formatter';
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
import { MultiSelect } from '@/components/common/multi-select';
import { AvatarUpload } from '@/components/common/avatar-upload';
import { apiClient } from '@/shared/api/client';
import { useApiData } from '@/shared/hooks/useApiData';
import type { AdminMst, RoleMst } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { AdminStatus, Gender, GenderLabels, AdminStatusLabels } from '@/shared/enums';
import { UPLOAD_CONFIG } from '@/shared/config/constant';
import { getAdminSchema, type AdminFormData } from '@/shared/validation/validation';
import type { AdminFormProps } from './types';

export function AdminForm({ initialData, onSuccess, onCancel }: AdminFormProps) {
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const queryClient = useQueryClient();
  const { create, update, loading } = useCrud<AdminMst>(ENDPOINTS.MASTER.ADMIN, {
    invalidateKeys: [], // Disable auto-invalidation to ensure sequence: Create/Update -> Role Update -> List Refresh
  });
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(() => initialData?.avatar ?? null);

  // Role data
  const { data: roles } = useApiData<RoleMst>(ENDPOINTS.MASTER.ROLE, {
    page: 1,
    per_page: 100,
    sort_by: 'created_at',
    sort_order: 'desc',
    staleTime: 0,
    refetchOnMount: 'always',
  });


  // Fetch assigned roles for Edit mode
  const { data: assignedRoles } = useApiData<{ admin_mst_id: number; role_mst_id: number }>(
    ENDPOINTS.JUNCTION.ADMIN_ROLE,
    {
      filters: {
        admin_mst_id: initialData?.id,
      },
      enabled: isEdit && !!initialData?.id,
      staleTime: 0,
      refetchOnMount: 'always',
    }
  );

  const [selectedRoleIds, setSelectedRoleIds] = useState<(string | number)[]>([]);
  const [initialRoleIds, setInitialRoleIds] = useState<number[]>([]);

  // Initialize selected roles when data is fetched
  useEffect(() => {
    if (assignedRoles && isEdit) {
      const roleIds = assignedRoles.map(item => item.role_mst_id);
      
      // Use JSON.stringify for array comparison to prevent infinite loops
      // caused by unstable object references from useApiData
      if (JSON.stringify(roleIds) !== JSON.stringify(initialRoleIds)) {
        setSelectedRoleIds(roleIds);
        setInitialRoleIds(roleIds);
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [assignedRoles, isEdit]); // Exclude initialRoleIds to prevent potential cycles if calculations are slightly off, though check guards it.

  const roleOptions = roles.map(role => ({
    value: role.id,
    label: role.name
  }));

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
        is_active: true,
        avatar: '',
      });
      setSelectedRoleIds([]);
      setInitialRoleIds([]);
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: AdminFormData) => {
    await execute(async () => {
      if (!isEdit && !data.password) {
        setError('password', { type: 'manual', message: tCommon('passwordRequired') });
        return;
      }

      try {
        // TODO: Handle avatar upload if avatarFile is present
        // For now, we'll just pass the data. Real implementation would look like:
        // if (avatarFile) {
        //   const uploadData = await upload(avatarFile);
        //   data.avatar = uploadData.url;
        // }

        const payload = {
          ...data,
          is_active: data.is_active ? IsActive.TRUE : IsActive.FALSE,
        };

        if (data.birth) {
          payload.birth = formatDateForBackend(data.birth);
        }

        let adminId: number | undefined;

        if (isEdit && initialData) {
          if (!payload.password) {
            delete payload.password;
          }
          await update(initialData.id, payload);
          adminId = initialData.id;
        } else {
          adminId = await create({
            ...payload,
            is_delete: false,
          });
        }

        // Handle role assignment
        if (adminId) {
            try {
                const currentRoleIds = selectedRoleIds.map(Number);
                
                if (isEdit) {
                    // Calculate diffs for Edit mode
                    const toInsert = currentRoleIds
                        .filter(id => !initialRoleIds.includes(id))
                        .map(roleId => ({
                            admin_mst_id: adminId!,
                            role_mst_id: roleId
                        }));

                    const toDelete = initialRoleIds
                        .filter(id => !currentRoleIds.includes(id))
                        .map(roleId => ({
                            admin_mst_id: adminId!,
                            role_mst_id: roleId
                        }));

                    if (toInsert.length > 0 || toDelete.length > 0) {
                        await apiClient.put(`${ENDPOINTS.JUNCTION.ADMIN_ROLE}/update`, {
                            insert: toInsert.length > 0 ? toInsert : undefined,
                            delete: toDelete.length > 0 ? toDelete : undefined,
                        });
                        // Update initial state after successful save
                        setInitialRoleIds(currentRoleIds); 
                    }
                } else if (selectedRoleIds.length > 0) {
                    // Create mode - only insert
                    await apiClient.put(`${ENDPOINTS.JUNCTION.ADMIN_ROLE}/update`, {
                      insert: selectedRoleIds.map(roleId => ({
                        admin_mst_id: adminId!,
                        role_mst_id: Number(roleId)
                      }))
                    });
                }
            } catch (roleError) {
                console.error('Failed to assign roles:', roleError);
                // We don't block success if role assignment fails
            }
        }



        // Manually invalidate list query after all operations (admin + roles) are complete
        await Promise.all([
            queryClient.invalidateQueries({ queryKey: [ENDPOINTS.MASTER.ADMIN] }),
            queryClient.invalidateQueries({ queryKey: [ENDPOINTS.JUNCTION.ADMIN_ROLE] })
        ]);
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

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="flex justify-center mb-4">
        <AvatarUpload
          value={avatarPreview ?? undefined}
          onChange={(file, preview) => {
            setAvatarFile(file);
            setAvatarPreview(preview);
            // In a real scenario, we might upload immediately or wait for submit
          }}
          maxSize={UPLOAD_CONFIG.DEFAULT_AVATAR_MAX_SIZE_MB}
        />
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="first_name">
            {tLabels('firstName')} <span className="text-red-500">*</span>
          </Label>
          <Input
            id="first_name"
            {...register('first_name')}
            className={errors.first_name ? 'border-red-500' : ''}
          />
          {errors.first_name && (
            <p className="text-sm text-red-500">{errors.first_name.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="last_name">
            {tLabels('lastName')} <span className="text-red-500">*</span>
          </Label>
          <Input
            id="last_name"
            {...register('last_name')}
            className={errors.last_name ? 'border-red-500' : ''}
          />
          {errors.last_name && (
            <p className="text-sm text-red-500">{errors.last_name.message}</p>
          )}
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="email">
            {tLabels('email')} <span className="text-red-500">*</span>
          </Label>
          <Input
            id="email"
            type="email"
            {...register('email')}
            className={errors.email ? 'border-red-500' : ''}
          />
          {errors.email && (
            <p className="text-sm text-red-500">{errors.email.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="user_name">
            {tLabels('username')} <span className="text-red-500">*</span>
          </Label>
          <Input
            id="user_name"
            {...register('user_name')}
            className={errors.user_name ? 'border-red-500' : ''}
          />
          {errors.user_name && (
            <p className="text-sm text-red-500">{errors.user_name.message}</p>
          )}
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="password">
          {tLabels('password')} {isEdit ? `(${tForms('leaveBlankToKeepCurrent')})` : <span className="text-red-500">*</span>}
        </Label>
        <Input
          id="password"
          type="password"
          {...register('password')}
          className={errors.password ? 'border-red-500' : ''}
          placeholder={isEdit ? tForms('passwordHidden') : tCommon('enterPassword')}
        />
        {errors.password && (
          <p className="text-sm text-red-500">{errors.password.message}</p>
        )}
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="phone_number">{tLabels('phoneNumber')}</Label>
          <Input
            id="phone_number"
            {...register('phone_number')}
            className={errors.phone_number ? 'border-red-500' : ''}
          />
          {errors.phone_number && (
            <p className="text-sm text-red-500">{errors.phone_number.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="birth">{tLabels('birthDate')}</Label>
          <Input
            id="birth"
            type="date"
            {...register('birth')}
            className={errors.birth ? 'border-red-500' : ''}
          />
          {errors.birth && (
            <p className="text-sm text-red-500">{errors.birth.message}</p>
          )}
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="address">{tLabels('address')}</Label>
        <Input
          id="address"
          {...register('address')}
          className={errors.address ? 'border-red-500' : ''}
        />
        {errors.address && (
          <p className="text-sm text-red-500">{errors.address.message}</p>
        )}
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="gender">
            {tLabels('gender')} <span className="text-red-500">*</span>
          </Label>
          <Select
            key={String(genderValue)}
            value={genderValue !== undefined && genderValue !== null ? String(genderValue) : ''}
            onValueChange={(value) => setValue('gender', Number(value) as Gender)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={Gender.MALE.toString()}>{GenderLabels[Gender.MALE]}</SelectItem>
              <SelectItem value={Gender.FEMALE.toString()}>{GenderLabels[Gender.FEMALE]}</SelectItem>
              <SelectItem value={Gender.OTHER.toString()}>{GenderLabels[Gender.OTHER]}</SelectItem>
            </SelectContent>
          </Select>
          {errors.gender && (
            <p className="text-sm text-red-500">{errors.gender.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="status">
            {tLabels('status')} <span className="text-red-500">*</span>
          </Label>
          <Select
            key={String(statusValue)}
            value={statusValue !== undefined && statusValue !== null ? String(statusValue) : ''}
            onValueChange={(value) => setValue('status', Number(value) as AdminStatus)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={AdminStatus.ACTIVE.toString()}>{AdminStatusLabels[AdminStatus.ACTIVE]}</SelectItem>
              <SelectItem value={AdminStatus.INACTIVE.toString()}>{AdminStatusLabels[AdminStatus.INACTIVE]}</SelectItem>
              <SelectItem value={AdminStatus.WAITING.toString()}>{AdminStatusLabels[AdminStatus.WAITING]}</SelectItem>
              <SelectItem value={AdminStatus.SUSPENDED.toString()}>{AdminStatusLabels[AdminStatus.SUSPENDED]}</SelectItem>
            </SelectContent>
          </Select>
          {errors.status && (
            <p className="text-sm text-red-500">{errors.status.message}</p>
          )}
        </div>
      </div>

      <div className="space-y-2">
        <MultiSelect
          label={tLabels('roles')}
          placeholder={tForms('selectRoles')}
          options={roleOptions}
          value={selectedRoleIds}
          onChange={setSelectedRoleIds}
        />
      </div>

      <div className="flex items-center gap-2 mt-4">
        <input
          type="checkbox"
          id="is_active"
          {...register('is_active')}
          className="rounded"
        />
        <Label htmlFor="is_active">{tLabels('isActive')}</Label>
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
