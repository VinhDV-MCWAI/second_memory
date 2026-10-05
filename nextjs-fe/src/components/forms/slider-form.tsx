'use client';

import { useEffect, useState } from 'react';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { ImageUpload } from '@/components/common/image-upload';
import type { SliderMgmt } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { StatusEnum, StatusEnumLabels } from '@/shared/enums';
import { getSliderSchema, type SliderFormData } from '@/shared/validation/validation';
import type { SliderFormProps } from './types';

export function SliderForm({ initialData, onSuccess, onCancel }: SliderFormProps) {
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<SliderMgmt>(ENDPOINTS.MANAGEMENT.SLIDER);
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [imagePreview, setImagePreview] = useState<string | null>(() => initialData?.image || null);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<SliderFormData>({
    resolver: zodResolver(getSliderSchema(tValidation)),
    defaultValues: {
      status: StatusEnum.PUBLISHED,
    },
  });

  useEffect(() => {
    if (initialData) {
      reset({
        title: initialData.title,
        image: initialData.image || '',
        link: initialData.link || '',
        slug: initialData.slug || '',

        status: initialData.status !== undefined ? Number(initialData.status) : StatusEnum.PUBLISHED,
      });
    } else {
      reset({
        title: '',
        image: '',
        link: '',
        slug: '',
        status: StatusEnum.PUBLISHED,
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: SliderFormData) => {
    await execute(async () => {
      try {
        const payload = { ...data };
      
      if (isEdit && initialData) {
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
  const statusValue = useWatch({ control, name: 'status' });

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <ImageUpload
            label={tCommon('sliderImage')}
            value={imagePreview ?? undefined}
            onChange={(file, preview) => {
              setImageFile(file);
              setImagePreview(preview);
            }}
            maxSize={10}
            shape="rectangle"
            aspectRatio="aspect-[21/9]"
          />
        </div>
        
        <div className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="title">{tCommon('title')} <span className="text-red-500">*</span></Label>
            <Input id="title" {...register('title')} className={errors.title ? 'border-red-500' : ''} />
            {errors.title && <p className="text-sm text-red-500">{errors.title.message}</p>}
          </div>

          <div className="space-y-2">
            <Label htmlFor="link">{tCommon('linkUrl')}</Label>
            <Input id="link" {...register('link')} placeholder={tForms('urlExample')} />
          </div>

          <div className="space-y-2">
            <Label htmlFor="slug">{tCommon('slug')}</Label>
            <Input id="slug" {...register('slug')} />
          </div>
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
            <Label htmlFor="status">{tCommon('status')} <span className="text-red-500">*</span></Label>
            <Select key={`status-${String(statusValue)}`} value={statusValue !== undefined && statusValue !== null ? String(statusValue) : ''} onValueChange={(value) => setValue('status', Number(value) as StatusEnum)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value={StatusEnum.PUBLISHED.toString()}>{StatusEnumLabels[StatusEnum.PUBLISHED]}</SelectItem>
                <SelectItem value={StatusEnum.DRAFT.toString()}>{StatusEnumLabels[StatusEnum.DRAFT]}</SelectItem>
                <SelectItem value={StatusEnum.ARCHIVED.toString()}>{StatusEnumLabels[StatusEnum.ARCHIVED]}</SelectItem>
              </SelectContent>
            </Select>
          </div>

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
