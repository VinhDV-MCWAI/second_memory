'use client';

import { useEffect } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { useCrud } from '@/shared/hooks/useCrud';
import { useApiData } from '@/shared/hooks/useApiData';
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
import type { TokenMst, AdminMst } from '@/shared/types/api';
import { ENDPOINTS } from '@/shared/api';
import { SORT_ORDER, PAGINATION } from '@/shared/config/constant';
import { getTokenSchema, type TokenFormData } from '@/shared/validation/validation';
import type { TokenFormProps } from './types';
import { formatDateForBackend } from '@/shared/utils/date-formatter';

export function TokenForm({ initialData, onSuccess, onCancel }: TokenFormProps) {
  const tCommon = useTranslations('common');
  const tForms = useTranslations('forms.placeholders');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud<TokenMst>(ENDPOINTS.MASTER.TOKEN);

  // Fetch admins for the dropdown
  const { data: admins, loading: adminsLoading } = useApiData<AdminMst>(
    ENDPOINTS.MASTER.ADMIN,
    { page: PAGINATION.DEFAULT_PAGE, per_page: PAGINATION.MAX_PER_PAGE, sort_by: 'email', sort_order: SORT_ORDER.ASC }
  );

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
    reset,
    setError,
  } = useForm<TokenFormData>({
    resolver: zodResolver(getTokenSchema(tValidation)),
    defaultValues: {
      account_id: 0,
      device_name: '',
    },
  });

  useEffect(() => {
    if (initialData) {
      reset({
        account_id: initialData.account_id || 0,
        device_name: initialData.device_name || '',
        ip_address: initialData.ip_address || '',
        expired_at: initialData.expired_at || '',
      });
    } else {
      reset({
        account_id: 0,
        device_name: '',
        ip_address: '',
        expired_at: '',
      });
    }
  }, [initialData, reset]);

  const { execute, isLoading: isActionProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const onSubmit = async (data: TokenFormData) => {
    await execute(async () => {
      try {
        // Convert string to number for account_id
        const payload = {
        ...data,
        account_id: Number(data.account_id),
        // Format date to dd/MM/yyyy for backend
        expired_at: data.expired_at ? formatDateForBackend(data.expired_at) : '',
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
  const accountIdValue = useWatch({ control, name: 'account_id' });

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="account_id">
          {tLabels('account')} <span className="text-red-500">*</span>
        </Label>
        <Select
          value={accountIdValue?.toString()}
          onValueChange={(value) => setValue('account_id', Number(value))}
          disabled={adminsLoading}
        >
          <SelectTrigger>
            <SelectValue placeholder={adminsLoading ? tCommon('loading') : tForms('selectAccount')} />
          </SelectTrigger>
          <SelectContent>
            {admins.map((admin) => (
              <SelectItem key={admin.id} value={admin.id.toString()}>
                {admin.email} ({admin.first_name} {admin.last_name})
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        {errors.account_id && (
          <p className="text-sm text-red-500">{errors.account_id.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="device_name">
          {tLabels('deviceName')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="device_name"
          {...register('device_name')}
          className={errors.device_name ? 'border-red-500' : ''}
          placeholder={tForms('deviceName')}
        />
        {errors.device_name && (
          <p className="text-sm text-red-500">{errors.device_name.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="ip_address">
          {tLabels('ipAddress')}
        </Label>
        <Input
          id="ip_address"
          {...register('ip_address')}
          placeholder={tForms('ipAddress')}
        />
      </div>

      <div className="space-y-2">
        <Label htmlFor="expired_at">
          {tLabels('expiredAt')}
        </Label>
        <Input
          id="expired_at"
          type="datetime-local"
          {...register('expired_at')}
        />
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
