'use client';

import { useState } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { useTranslations } from 'next-intl';
import type { PermissionGroup, PermissionManagerProps } from '@/shared/types/data-table.types';

export type { Permission, PermissionGroup } from '@/shared/types/data-table.types';

export function PermissionManager({
  permissions,
  selectedPermissions = [],
  onChange,
  disabled = false,
}: PermissionManagerProps) {
  const [selected, setSelected] = useState<number[]>(selectedPermissions);
  const t = useTranslations('permissions');

  const handleToggle = (permissionId: number) => {
    const newSelected = selected.includes(permissionId)
      ? selected.filter((id) => id !== permissionId)
      : [...selected, permissionId];
    
    setSelected(newSelected);
    onChange(newSelected);
  };

  const handleToggleGroup = (group: PermissionGroup) => {
    const groupPermissionIds = group.permissions.map((p) => p.id);
    const allSelected = groupPermissionIds.every((id) => selected.includes(id));

    const newSelected = allSelected
      ? selected.filter((id) => !groupPermissionIds.includes(id))
      : [...new Set([...selected, ...groupPermissionIds])];

    setSelected(newSelected);
    onChange(newSelected);
  };

  const isGroupFullySelected = (group: PermissionGroup) => {
    return group.permissions.every((p) => selected.includes(p.id));
  };

  const isGroupPartiallySelected = (group: PermissionGroup) => {
    const selectedCount = group.permissions.filter((p) => selected.includes(p.id)).length;
    return selectedCount > 0 && selectedCount < group.permissions.length;
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-lg font-medium">{t('title')}</h3>
          <p className="text-sm text-muted-foreground">
            {t('description')}
          </p>
        </div>
        <Badge variant="secondary">
          {t('selected', { count: selected.length })}
        </Badge>
      </div>

      <Separator />

      <div className="space-y-4">
        {permissions.map((group) => (
          <Card key={group.name}>
            <CardHeader className="pb-3">
              <div className="flex items-center gap-3">
                <Checkbox
                  id={`group-${group.name}`}
                  checked={isGroupFullySelected(group)}
                  onCheckedChange={() => handleToggleGroup(group)}
                  disabled={disabled}
                  className={
                    isGroupPartiallySelected(group) ? 'data-[state=checked]:bg-primary/50' : ''
                  }
                />
                <div className="flex-1">
                  <CardTitle className="text-base">{group.name}</CardTitle>
                  <CardDescription className="text-xs">
                    {t('permissionsCount', { count: group.permissions.length })}
                  </CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid gap-3 md:grid-cols-2">
                {group.permissions.map((permission) => (
                  <div key={permission.id} className="flex items-start gap-3">
                    <Checkbox
                      id={`permission-${permission.id}`}
                      checked={selected.includes(permission.id)}
                      onCheckedChange={() => handleToggle(permission.id)}
                      disabled={disabled}
                    />
                    <div className="flex-1">
                      <Label
                        htmlFor={`permission-${permission.id}`}
                        className="cursor-pointer text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                      >
                        {permission.name}
                      </Label>
                      {permission.description && (
                        <p className="text-xs text-muted-foreground mt-1">
                          {permission.description}
                        </p>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
