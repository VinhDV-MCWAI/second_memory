'use client';

import { useEffect } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
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
import type { SettingLinkMgmt } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { StatusEnum, StatusEnumLabels, IsActive } from '@/shared/enums';
import { getSettingLinkSchema, type SettingLinkFormData } from '@/shared/validation/validation';
import type { SettingLinkFormProps } from './types';

export function SettingLinkForm({ initialData, onSuccess, onCancel }: SettingLinkFormProps) {
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<SettingLinkMgmt>(ENDPOINTS.MANAGEMENT.SETTING_LINK);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<SettingLinkFormData>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(getSettingLinkSchema(tValidation)) as any,
    defaultValues: {
      rank_order: 0,
      status: StatusEnum.PUBLISHED,
      is_active: true,
    },
  });

  useEffect(() => {
    if (initialData) {
      reset({
        name: initialData.name,
        url: initialData.url,
        description: initialData.description || '',
        rank_order: initialData.rank_order,
        status: initialData.status,
        is_active: initialData.is_active,
      });
    } else {
      reset({
        name: '',
        url: '',
        description: '',
        rank_order: 0,
        status: StatusEnum.PUBLISHED,
        is_active: true,
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: SettingLinkFormData) => {
    await execute(async () => {
      try {
        // Convert form data to API payload format
        const payload = {
          name: data.name,
          link: data.url, // Map url to link
          rank_order: Number(data.rank_order),
          is_active: data.is_active ? IsActive.TRUE : IsActive.FALSE,
          is_delete: false,
        };
      
      if (isEdit && initialData) {
        await update(initialData.id, payload);
      } else {
        await create(payload);
      }
      onSuccess();
    } catch (error: unknown) {
      console.error(error);
      handleBindErrors(error, setError);
    }
    });
  };

  // Use useWatch hook instead of watch() to avoid React Compiler issues
  const statusValue = useWatch({ control, name: 'status' });

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="name">
          {tCommon('name')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="name"
          {...register('name')}
          className={errors.name ? 'border-red-500' : ''}
          placeholder={tForms('privacyPolicy')}
        />
        {errors.name && (
          <p className="text-sm text-red-500">{errors.name.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="url">
          {tCommon('url')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="url"
          {...register('url')}
          className={errors.url ? 'border-red-500' : ''}
          placeholder={tForms('privacyUrl')}
        />
        {errors.url && (
          <p className="text-sm text-red-500">{errors.url.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="description">{tCommon('description')}</Label>
        <Textarea id="description" {...register('description')} rows={3} />
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="rank_order">
            {tCommon('displayOrder')} <span className="text-red-500">*</span>
          </Label>
          <Input
            id="rank_order"
            type="number"
            {...register('rank_order')}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="status">
            {tCommon('status')} <span className="text-red-500">*</span>
          </Label>
          <Select
            value={statusValue?.toString()}
            onValueChange={(value) => setValue('status', Number(value) as StatusEnum)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={StatusEnum.PUBLISHED.toString()}>{StatusEnumLabels[StatusEnum.PUBLISHED]}</SelectItem>
              <SelectItem value={StatusEnum.DRAFT.toString()}>{StatusEnumLabels[StatusEnum.DRAFT]}</SelectItem>
              <SelectItem value={StatusEnum.ARCHIVED.toString()}>{StatusEnumLabels[StatusEnum.ARCHIVED]}</SelectItem>
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
        <Label htmlFor="is_active">{tCommon('isActive')}</Label>
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
