'use client';

import { useEffect, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { IsActive } from '@/shared/enums/enums';
import type { components } from '@/shared/types/openapi';
import { useCrud } from '@/shared/hooks/use-crud';
import { useActionLock } from '@/shared/hooks/use-action-lock';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { formatDateForBackend } from '@/shared/utils/date-formatter';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FormField } from '@/components/common/form-field';
import { Textarea } from '@/components/ui/textarea';
import { OptionSelect } from '@/components/common/option-select';
import { enumOptions } from '@/shared/utils/enum-options';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { HistoryViewer } from '@/features/history/components/history-viewer';
import { AvatarUpload } from '@/components/common/avatar-upload';
import { ENDPOINTS } from '@/shared/api';
import { Gender, GenderLabels, UserStatus, UserStatusLabels } from '@/shared/enums/enums';
import { FILE_UPLOAD } from '@/shared/config';
import { getUserSchema, type UserFormData } from '@/shared/validation/validation';
import type { UserMgmt } from '@/shared/types/api';
import type { ResourceFormProps } from '@/components/common/resource-list-page';

export function UserForm({ initialData, onSuccess, onCancel }: ResourceFormProps<UserMgmt>) {
  const tCommon = useTranslations('common');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud(ENDPOINTS.MANAGEMENT.USER);
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(
    () => initialData?.avatar || null,
  );

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<UserFormData>({
    resolver: zodResolver(getUserSchema(tValidation)),
    defaultValues: {
      gender: Gender.MALE,
      status: UserStatus.ACTIVE,
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
        birth: initialData.birth ? initialData.birth.slice(0, 10) : '',

        gender: initialData.gender ? Number(initialData.gender) : Gender.MALE,
        status: initialData.status !== undefined ? Number(initialData.status) : UserStatus.ACTIVE,
        is_active: initialData.is_active,
        password: '',
      });
    } else {
      reset({
        email: '',
        user_name: '',
        first_name: '',
        last_name: '',
        address: '',
        phone_number: '',
        birth: '',
        gender: Gender.MALE,
        status: UserStatus.ACTIVE,
        is_active: true,
        password: '',
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({
    delay: UI_CONSTANTS.ACTION_DELAY_MS,
  });

  const onSubmit = async (data: UserFormData) => {
    await execute(async () => {
      try {
        const { password, ...otherData } = data;
        const payload: Omit<components['schemas']['UpdateUserMgmtRequest'], 'id'> = {
          ...otherData,
          is_active: otherData.is_active ? IsActive.TRUE : IsActive.FALSE,
        };

        if (password) {
          payload.password = password;
        }

        if (data.birth) {
          payload.birth = formatDateForBackend(data.birth);
        }

        // Handle avatar upload logic or payload construction here if needed
        // Currently just passing fields, assuming backend or pre-upload handles file
        // NOTE: Real implementation would need FormData or separate upload call if file selected

        if (isEdit && initialData) {
          await update(initialData.id, { ...payload });
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

  const FormContent = (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="mb-4 flex justify-center">
        <AvatarUpload
          value={avatarPreview ?? undefined}
          onChange={(file, preview) => {
            setAvatarFile(file);
            setAvatarPreview(preview);
          }}
          maxSize={FILE_UPLOAD.MAX_AVATAR_SIZE_MB}
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

      <FormField id="email" label={tLabels('email')} required error={errors.email?.message}>
        <Input
          id="email"
          type="email"
          {...register('email')}
          className={errors.email ? 'border-red-500' : ''}
        />
      </FormField>

      <div className="space-y-2">
        <Label htmlFor="password">
          {isEdit ? tLabels('passwordOptional') : tLabels('password')}{' '}
          {!isEdit && <span className="text-red-500">*</span>}
        </Label>
        <Input
          id="password"
          type="password"
          {...register('password')}
          className={errors.password ? 'border-red-500' : ''}
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
        <Textarea
          id="address"
          {...register('address')}
          rows={2}
          className={errors.address ? 'border-red-500' : ''}
        />
      </FormField>

      <div className="grid grid-cols-2 gap-4">
        <FormField id="gender" label={tLabels('gender')} required>
          <OptionSelect
            value={genderValue}
            onChange={(value) => setValue('gender', Number(value) as Gender)}
            options={enumOptions(GenderLabels)}
          />
        </FormField>

        <FormField id="status" label={tLabels('status')} required>
          <OptionSelect
            value={statusValue}
            onChange={(value) => setValue('status', Number(value) as UserStatus)}
            options={enumOptions(UserStatusLabels)}
          />
        </FormField>
      </div>

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
  );

  if (!isEdit) {
    return FormContent;
  }

  return (
    <Tabs defaultValue="details" className="w-full">
      <TabsList className="grid w-full grid-cols-2">
        <TabsTrigger value="details">{tLabels('details')}</TabsTrigger>
        <TabsTrigger value="history">{tLabels('history')}</TabsTrigger>
      </TabsList>
      <TabsContent value="details" className="mt-4">
        {FormContent}
      </TabsContent>
      <TabsContent value="history" className="mt-4">
        <div className="h-[400px] overflow-y-auto pr-2">
          {initialData && (
            <HistoryViewer
              endpoint={`${ENDPOINTS.MANAGEMENT.USER}-hist`}
              foreignKey="user_mgmt_id"
              recordId={initialData.id}
            />
          )}
        </div>
      </TabsContent>
    </Tabs>
  );
}
