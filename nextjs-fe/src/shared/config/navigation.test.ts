import { existsSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';
import { NAVIGATION_MENU, isRouteActive } from './navigation';
import type { MenuItem } from '@/shared/types';

const hrefsOf = (items: MenuItem[]): string[] =>
  items.flatMap((item) => [...(item.href ? [item.href] : []), ...hrefsOf(item.children ?? [])]);

describe('isRouteActive', () => {
  it('matches the route itself and its nested routes only', () => {
    expect(isRouteActive('/admin/roles', '/admin/roles')).toBe(true);
    expect(isRouteActive('/admin/roles/42', '/admin/roles')).toBe(true);
    expect(isRouteActive('/admin/roles-archive', '/admin/roles')).toBe(false);
    expect(isRouteActive('/admin/roles', undefined)).toBe(false);
  });
});

describe('NAVIGATION_MENU', () => {
  // Guards module removals: a menu entry must not outlive its page.
  it('links only to pages that exist', () => {
    const missing = hrefsOf(NAVIGATION_MENU).filter(
      (href) => !existsSync(path.join(process.cwd(), 'src/app', href, 'page.tsx')),
    );
    expect(missing).toEqual([]);
  });
});
