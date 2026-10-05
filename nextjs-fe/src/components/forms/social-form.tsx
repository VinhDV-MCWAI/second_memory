'use client';

import { useEffect } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { IsActive } from '@/shared/enums/enums';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
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
import type { SocialMgmt } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { SocialStatus, SocialStatusLabels } from '@/shared/enums';
import { getSocialSchema, type SocialFormData } from '@/shared/validation/validation';
import type { SocialFormProps } from './types';

export function SocialForm({ initialData, onSuccess, onCancel }: SocialFormProps) {
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<SocialMgmt>(ENDPOINTS.MANAGEMENT.SOCIAL);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<SocialFormData>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(getSocialSchema(tValidation)) as any,
    defaultValues: {
      rank_order: 0,
      status: SocialStatus.ACTIVE,
    },
  });

  useEffect(() => {
    if (initialData) {
      reset({
        name: initialData.name,
        link: initialData.link,
        image: initialData.image || '',
        rank_order: initialData.rank_order,
        status: Number(initialData.status),
        is_display: initialData.is_display,
      });
    } else {
      reset({
        name: '',
        link: '',
        image: '',
        rank_order: 0,
        status: SocialStatus.ACTIVE,
        is_display: true,
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: SocialFormData) => {
    await execute(async () => {
      try {
        // Convert form data to API payload format
        const payload = {
          ...data,
          rank_order: Number(data.rank_order),
          is_display: data.is_display ? IsActive.TRUE : IsActive.FALSE,
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
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="name">
            {tCommon('name')} <span className="text-red-500">*</span>
          </Label>
          <Input
            id="name"
            {...register('name')}
            className={errors.name ? 'border-red-500' : ''}
            placeholder={tForms('socialName')}
          />
          {errors.name && (
            <p className="text-sm text-red-500">{errors.name.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="image">{tLabels('iconImageUrl')}</Label>
          <Input
            id="image"
            {...register('image')}
            placeholder={tForms('socialIcon')}
          />
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="link">
          {tCommon('link')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="link"
          {...register('link')}
          className={errors.link ? 'border-red-500' : ''}
          placeholder={tForms('socialUrl')}
        />
        {errors.link && (
          <p className="text-sm text-red-500">{errors.link.message}</p>
        )}
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
            className={errors.rank_order ? 'border-red-500' : ''}
          />
          {errors.rank_order && (
            <p className="text-sm text-red-500">{errors.rank_order.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="status">
            {tLabels('status')} <span className="text-red-500">*</span>
          </Label>
          <Select
            value={statusValue?.toString()}
            onValueChange={(value) => setValue('status', Number(value) as SocialStatus)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={SocialStatus.ACTIVE.toString()}>{SocialStatusLabels[SocialStatus.ACTIVE]}</SelectItem>
              <SelectItem value={SocialStatus.INACTIVE.toString()}>{SocialStatusLabels[SocialStatus.INACTIVE]}</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="flex items-center gap-2 mt-4">
        <input
          type="checkbox"
          id="is_display"
          {...register('is_display')}
          className="rounded"
        />
        <Label htmlFor="is_display">{tCommon('isDisplay')}</Label>
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
