'use client';

import { useTranslations } from 'next-intl';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { IsActive, IsActiveLabels } from '@/shared/enums';
import type { Step1RoleSetupProps } from '@/shared/types/role-wizard.types';

export function Step1RoleSetup({ data, onChange }: Step1RoleSetupProps) {
  const tLabels = useTranslations('forms.labels');
  const tWizard = useTranslations('roleWizard');

  return (
    <form className="space-y-6">
      <div className="space-y-2">
        <Label htmlFor="name">
          {tLabels('name')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="name"
          placeholder={tWizard('enterRoleName')}
          value={data.name || ''}
          onChange={(e) => onChange({ ...data, name: e.target.value })}
        />
        <p className="text-xs text-gray-500">
          {tWizard('roleNameHelp')}
        </p>
      </div>

      <div className="space-y-2">
        <Label htmlFor="permission">
          {tLabels('permission')} <span className="text-red-500">*</span>
        </Label>
        <Input
          id="permission"
          placeholder={tWizard('enterPermission')}
          value={data.permission || ''}
          onChange={(e) => onChange({ ...data, permission: e.target.value })}
        />
        <p className="text-xs text-gray-500">
          {tWizard('permissionHelp')}
        </p>
      </div>

      <div className="space-y-2">
        <Label htmlFor="is_active">
          {tLabels('status')} <span className="text-red-500">*</span>
        </Label>
        <Select
          value={data.is_active ? IsActive.TRUE.toString() : IsActive.FALSE.toString()}
          onValueChange={(value) =>
            onChange({ ...data, is_active: value === IsActive.TRUE.toString() })
          }
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={IsActive.TRUE.toString()}>
              {IsActiveLabels[IsActive.TRUE]}
            </SelectItem>
            <SelectItem value={IsActive.FALSE.toString()}>
              {IsActiveLabels[IsActive.FALSE]}
            </SelectItem>
          </SelectContent>
        </Select>
        <p className="text-xs text-gray-500">
          {tWizard('statusHelp')}
        </p>
      </div>
    </form>
  );
}
