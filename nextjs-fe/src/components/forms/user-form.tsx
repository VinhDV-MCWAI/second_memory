'use client';

import { useEffect, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { IsActive } from '@/shared/enums/enums';
import type { UserUpdatePayload, UserCreatePayload } from '@/shared/types/payloads';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { formatDateForBackend } from '@/shared/utils/date-formatter';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { HistoryViewer } from '@/components/features/history/history-viewer';
import { AvatarUpload } from '@/components/common/avatar-upload';
import type { UserMgmt } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { Gender, GenderLabels, UserStatus, UserStatusLabels } from '@/shared/enums/enums';
import { FILE_UPLOAD } from '@/shared/config/constant';
import { getUserSchema, type UserFormData } from '@/shared/validation/validation';
import type { UserFormProps } from './types';

export function UserForm({ initialData, onSuccess, onCancel }: UserFormProps) {
  const tCommon = useTranslations('common');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<UserMgmt>(ENDPOINTS.MANAGEMENT.USER);
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(() => initialData?.avatar || null);

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

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: UserFormData) => {
    await execute(async () => {
      try {
        const { password, ...otherData } = data;
      const payload: UserUpdatePayload | UserCreatePayload = {
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
      <div className="flex justify-center mb-4">
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
        <Label htmlFor="password">
          {isEdit ? tLabels('passwordOptional') : tLabels('password')} {(!isEdit) && <span className="text-red-500">*</span>}
        </Label>
        <Input
          id="password"
          type="password"
          {...register('password')}
          className={errors.password ? 'border-red-500' : ''}
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
        <Textarea
          id="address"
          {...register('address')}
          rows={2}
          className={errors.address ? 'border-red-500' : ''}
        />
        {errors.address && (
          <p className="text-sm text-red-500">{errors.address.message}</p>
        )}
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="gender">{tLabels('gender')} <span className="text-red-500">*</span></Label>
          <Select
            key={`gender-${String(genderValue)}`}
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
        </div>

        <div className="space-y-2">
          <Label htmlFor="status">
            {tLabels('status')} <span className="text-red-500">*</span>
          </Label>
          <Select
            key={`status-${String(statusValue)}`}
            value={statusValue !== undefined && statusValue !== null ? String(statusValue) : ''}
            onValueChange={(value) => setValue('status', Number(value) as UserStatus)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {Object.entries(UserStatusLabels).map(([value, label]) => (
                <SelectItem key={value} value={value.toString()}>
                  {label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
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
              entityType="user"
              entityId={initialData.id}
              endpoint={`${ENDPOINTS.MANAGEMENT.USER}-hist`}
            />
          )}
        </div>
      </TabsContent>
    </Tabs>
  );
}
