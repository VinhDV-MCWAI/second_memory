'use client';

import { useTranslations } from 'next-intl';
import { notification } from '@/shared/utils/notification';
import { Label } from '@/components/ui/label';
import { useRegistrationSetting } from '@/features/master/hooks/use-registration-setting';
import { usePermissions } from '@/shared/hooks/use-permissions';

/** Sign-up switch above the end-user list (ADR-0020). Read-only without the update permission. */
export function RegistrationSwitch() {
  const t = useTranslations('endUsers');
  const { can } = usePermissions();
  const { setting, update } = useRegistrationSetting();
  const canChange = can('end-user.registration-update');

  const onChange = async (open: boolean) => {
    await update.mutateAsync(open);
    notification.success(t('registrationSaved'));
  };

  return (
    <div className="mb-4 rounded-md border p-3">
      <div className="flex items-center gap-2">
        <input
          type="checkbox"
          id="registration-open"
          className="rounded"
          checked={setting.data ?? false}
          disabled={!canChange || setting.isPending || update.isPending}
          onChange={(event) => void onChange(event.target.checked)}
        />
        <Label htmlFor="registration-open">{t('registrationOpen')}</Label>
      </div>
      <p className="mt-1 text-sm text-muted-foreground">{t('registrationHint')}</p>
    </div>
  );
}
