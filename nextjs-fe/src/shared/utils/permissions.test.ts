import { describe, expect, it } from 'vitest';
import { LayoutDashboard } from 'lucide-react';
import { actionOfPermission, featureOfEndpoint, filterMenu, permissionName } from './permissions';
import type { MenuItem } from '@/shared/types';

describe('permission names', () => {
  it('match the API route names', () => {
    expect(featureOfEndpoint('/admin/learning-goal')).toBe('learning-goal');
    expect(permissionName('learning-goal', 'store')).toBe('learning-goal.store');
    expect(actionOfPermission('skill-level.store')).toBe('store');
  });
});

describe('filterMenu', () => {
  const item = (label: string, permission?: string): MenuItem => ({
    label,
    icon: LayoutDashboard,
    href: `/${label}`,
    permission,
  });
  const menu: MenuItem[] = [
    item('home'),
    {
      label: 'ledger',
      icon: LayoutDashboard,
      children: [item('skills', 'skill.list'), item('tags', 'tag.list')],
    },
    { label: 'master', icon: LayoutDashboard, children: [item('admins', 'admin-mst.list')] },
  ];

  it('keeps allowed entries and drops groups left empty', () => {
    const allowed = filterMenu(menu, (permission) => permission === 'tag.list');

    expect(allowed.map((entry) => entry.label)).toEqual(['home', 'ledger']);
    expect(allowed[1].children?.map((entry) => entry.label)).toEqual(['tags']);
  });
});
