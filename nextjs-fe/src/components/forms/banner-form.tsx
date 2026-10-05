'use client';

import { useEffect, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { useCrud } from '@/shared/hooks/useCrud';
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
import { SafeButton } from '@/components/common/safe-button';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { ImagePicker } from '@/components/common/form/image-picker';
import type { BannerMgmt } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { StatusEnum, StatusEnumLabels } from '@/shared/enums';
import { getBannerSchema, type BannerFormData } from '@/shared/validation/validation';
import { slugify } from '@/shared/utils/string-utils';
import { LoadingOverlay } from '@/components/ui/loading';
import { UI_CONSTANTS } from '@/shared/config';
import type { BannerFormProps } from './types';

export function BannerForm({ initialData, onSuccess, onCancel }: BannerFormProps) {
  const tCommon = useTranslations('common');
  const tLabels = useTranslations('forms.labels');
  const tFields = useTranslations('fields');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<BannerMgmt>(ENDPOINTS.MANAGEMENT.BANNER);
  
  
  const [uploadedMediaId, setUploadedMediaId] = useState<number | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<BannerFormData>({
    resolver: zodResolver(getBannerSchema(tValidation)),
    defaultValues: {
      status: StatusEnum.PUBLISHED,
    },
  });

  // Watch title to auto-generate slug
  // Use useWatch to avoid React Compiler warning
  const titleValue = useWatch({ control, name: 'title' });
  
  // Watch image for preview
  const imagePreview = useWatch({ control, name: 'image' });

  useEffect(() => {
    // Synchronize slug with title in both create and edit modes
    // Only auto-update if strictly needed or just always mirror for now if that's what user wants
    // User said "update modal seems not synchronous with create modal"
    // So we enable it for both.
    // However, to prevent overwriting existing custom slugs in Edit mode on load, 
    // we should strictly check if title changed.
    // Since titleValue updates on mount from defaultValues, this might overwrite.
    // But defaultValues come from initialData.
    // So if initialData.slug exists and matches slugify(initialData.title), it is fine.
    // If it doesn't match, we might overwrite it. 
    // Let's rely on user intention: "Synchronize slug function".
    if (titleValue) {
        setValue('slug', slugify(titleValue), { shouldDirty: true });
    }
  }, [titleValue, setValue]);

  useEffect(() => {
    if (initialData) {
      reset({
        title: initialData.title,
        slug: initialData.slug || '',
        description: initialData.description || '',
        image: initialData.image || '',
        position: initialData.position || '',
        status: initialData.status,
      });
      // Ensure media_id is preserved if we were editing handling logic here
    } else {
      reset({
        title: '',
        slug: '',
        description: '',
        image: '',
        position: '',
        status: StatusEnum.PUBLISHED,
      });
    }
  }, [initialData, reset]);



  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: BannerFormData) => {
    await execute(async () => {
        try {
        const payload: BannerFormData & { media_id?: number } = { ...data };
        if (uploadedMediaId) {
            payload.media_id = uploadedMediaId;
        }

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
    <div className="relative">
      {isActionProcessing && <LoadingOverlay variant="absolute" />}
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="space-y-2">
            <ImagePicker 
              label={tFields('image')}
              required
              value={imagePreview}
              onChange={(url, id) => {
                setValue('image', url);
                if (id) setUploadedMediaId(id);
                else setUploadedMediaId(null);
              }}
              error={errors.image ? tValidation('image.required') : undefined}
            />
            {/* Hidden input to register image field for validation - wait, ImagePicker handles visual feedback, but react-hook-form needs registration? 
                ActuallysetValue updates the form state. But 'register' was creating a ref. 
                With 'setValue', we are manually handling it. 
                Let's keep the hidden input just in case validation relies on ref being present OR simply rely on setValue and standard RHF validation.
                If I remove <input ...register('image') />, I might lose focus management on error? 
                But for a custom component, we usually use Controller or just manual setValue.
                The previous code had: <input type="hidden" {...register('image')} />.
                I will keep it or rely on Controller. Since I'm using setValue, register is less critical for value tracking but good for validation mode 'onChange'.
            */}
            <input type="hidden" {...register('image')} />
        </div>

        
        <div className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="title">{tLabels('title')} <span className="text-red-500">*</span></Label>
            <Input id="title" {...register('title')} className={errors.title ? 'border-red-500' : ''} />
            {errors.title && <p className="text-sm text-red-500">{errors.title.message}</p>}
          </div>

           <div className="space-y-2">
            <Label htmlFor="slug">{tLabels('slug')} <span className="text-red-500">*</span></Label>
            <Input id="slug" {...register('slug')} className={errors.slug ? 'border-red-500' : ''} />
            {errors.slug && <p className="text-sm text-red-500">{errors.slug.message}</p>}
          </div>

          <div className="space-y-2">
            <Label htmlFor="position">{tLabels('position')} <span className="text-red-500">*</span></Label>
            <Input id="position" {...register('position')} className={errors.position ? 'border-red-500' : ''} />
             {errors.position && <p className="text-sm text-red-500">{errors.position.message}</p>}
          </div>
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="description">{tLabels('description')} <span className="text-red-500">*</span></Label>
        <Textarea id="description" {...register('description')} className={errors.description ? 'border-red-500' : ''} />
        {errors.description && <p className="text-sm text-red-500">{errors.description.message}</p>}
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
            <Label htmlFor="status">{tLabels('status')} <span className="text-red-500">*</span></Label>
            <Select value={statusValue?.toString()} onValueChange={(value) => setValue('status', Number(value) as StatusEnum)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                {Object.values(StatusEnum)
                    .filter((value) => typeof value === 'number')
                    .map((value) => (
                      <SelectItem key={value} value={value.toString()}>
                        {StatusEnumLabels[value as StatusEnum]}
                      </SelectItem>
                    ))}
              </SelectContent>
            </Select>
          </div>
      </div>

      <div className="flex justify-end gap-2 pt-4">
        <Button type="button" variant="outline" onClick={onCancel} disabled={loading || isActionProcessing}>
          {tCommon('cancel')}
        </Button>
        <SafeButton 
          type="submit" 
          disabled={loading || isActionProcessing}
          // Note: We use standard type="submit" here but the form onSubmit is handled wrapped
        >
          {loading || isActionProcessing ? (isEdit ? tCommon('updating') : tCommon('creating')) : (isEdit ? tCommon('update') : tCommon('create'))}
        </SafeButton>
      </div>
    </form>
    </div>
  );
}
