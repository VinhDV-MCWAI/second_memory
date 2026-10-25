'use client';

import { useTranslations } from 'next-intl';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import type { PermissionGroup } from '@/shared/types/models';
import { PERMISSION_ACTIONS } from '@/shared/utils/permissions';

/** The four CRUD actions get their own column; any other action (import, query, …) goes to "other". */
const COLUMNS: string[] = Object.values(PERMISSION_ACTIONS);

interface PermissionMatrixProps {
  groups: PermissionGroup[];
  value: string[];
  onChange: (permissions: string[]) => void;
  disabled?: boolean;
}

/** One row per feature, one checkbox per permission; the first column toggles the whole row. */
export function PermissionMatrix({ groups, value, onChange, disabled }: PermissionMatrixProps) {
  const t = useTranslations('permissions');
  const selected = new Set(value);

  const set = (names: string[], checked: boolean) => {
    const next = new Set(selected);
    names.forEach((name) => (checked ? next.add(name) : next.delete(name)));
    onChange([...next].sort());
  };

  const label = (key: string, fallback: string) => (t.has(key) ? t(key) : fallback);

  return (
    // The header with the action names stays in view while the features scroll under it
    <Table containerClassName="max-h-[55vh] rounded-md border">
      <TableHeader className="sticky top-0 z-10 bg-muted">
        <TableRow>
          <TableHead>{t('feature')}</TableHead>
          {COLUMNS.map((action) => (
            <TableHead key={action} className="text-center">
              {t(`actions.${action}`)}
            </TableHead>
          ))}
          <TableHead>{t('actions.other')}</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {groups.map((group) => {
          const names = group.permissions.map((permission) => permission.name);
          const all = names.every((name) => selected.has(name));
          const featureLabel = label(`features.${group.feature}`, group.feature);
          const others = group.permissions.filter((p) => !COLUMNS.includes(p.action));
          return (
            <TableRow key={group.feature}>
              <TableCell>
                <label className="flex items-center gap-2">
                  <Checkbox
                    checked={all}
                    disabled={disabled}
                    aria-label={t('wholeFeature', { feature: featureLabel })}
                    onCheckedChange={(checked) => set(names, checked === true)}
                  />
                  {featureLabel}
                </label>
              </TableCell>
              {COLUMNS.map((action) => {
                const permission = group.permissions.find((p) => p.action === action);
                return (
                  <TableCell key={action} className="text-center">
                    {permission && (
                      <Checkbox
                        checked={selected.has(permission.name)}
                        disabled={disabled}
                        aria-label={permission.name}
                        onCheckedChange={(checked) => set([permission.name], checked === true)}
                      />
                    )}
                  </TableCell>
                );
              })}
              <TableCell>
                <div className="flex flex-wrap gap-3">
                  {others.map((permission) => (
                    <label key={permission.name} className="flex items-center gap-1 text-sm">
                      <Checkbox
                        checked={selected.has(permission.name)}
                        disabled={disabled}
                        aria-label={permission.name}
                        onCheckedChange={(checked) => set([permission.name], checked === true)}
                      />
                      {label(`actions.${permission.action}`, permission.action)}
                    </label>
                  ))}
                </div>
              </TableCell>
            </TableRow>
          );
        })}
      </TableBody>
    </Table>
  );
}
