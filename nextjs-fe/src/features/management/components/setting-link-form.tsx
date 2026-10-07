'use client';

import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { useCrud } from '@/shared/hooks/use-crud';
import { useActionLock } from '@/shared/hooks/use-action-lock';
import { UI_CONSTANTS } from '@/shared/config';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ENDPOINTS } from '@/shared/api';
import { IsDelete } from '@/shared/enums';
import { getSettingLinkSchema, type SettingLinkFormData } from '@/shared/validation/validation';
import type { SettingLinkMgmt } from '@/shared/types/api';
import type { ResourceFormProps } from '@/components/common/resource-list-page';

export function SettingLinkForm({
  initialData,
  onSuccess,
  onCancel,
}: ResourceFormProps<SettingLinkMgmt>) {
  const tCommon = useTranslations('common');
  const tFields = useTranslations('fields');
  const tForms = useTranslations('forms.placeholders');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud(ENDPOINTS.MANAGEMENT.SETTING_LINK);

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
    setError,
  } = useForm<SettingLinkFormData>({
    resolver: zodResolver(getSettingLinkSchema(tValidation)),
    defaultValues: { key: '', value: '' },
  });

  useEffect(() => {
    reset({ key: initialData?.key ?? '', value: initialData?.value ?? '' });
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({
    delay: UI_CONSTANTS.ACTION_DELAY_MS,
  });

  const onSubmit = async (data: SettingLinkFormData) => {
    await execute(async () => {
      try {
        const payload = { ...data, is_delete: IsDelete.FALSE };
        if (isEdit && initialData) {
          await update(initialData.id, payload);
        } else {
          await create(payload);
        }
        onSuccess();
      } catch (error: unknown) {
        handleBindErrors(error, setError);
      }
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="key">
          {tFields('key')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="key"
          {...register('key')}
          className={errors.key ? 'border-red-500' : ''}
          placeholder={tForms('settingKey')}
        />
        {errors.key && <p className="text-sm text-red-500">{errors.key.message}</p>}
      </div>

      <div className="space-y-2">
        <Label htmlFor="value">
          {tFields('value')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="value"
          {...register('value')}
          className={errors.value ? 'border-red-500' : ''}
          placeholder={tForms('settingValue')}
        />
        {errors.value && <p className="text-sm text-red-500">{errors.value.message}</p>}
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
}
