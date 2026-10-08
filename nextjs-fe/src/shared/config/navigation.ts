import { LayoutDashboard, Database, UserCog } from 'lucide-react';
import { MenuItem } from '@/shared/types';
import { ADMIN_ROUTES } from '@/shared/config';

export const NAVIGATION_MENU: MenuItem[] = [
  {
    label: 'navigation.dashboard',
    icon: LayoutDashboard,
    href: ADMIN_ROUTES.DASHBOARD,
  },
  {
    label: 'navigation.masterData',
    icon: Database,
    children: [
      {
        label: 'entities.admins',
        icon: UserCog,
        href: ADMIN_ROUTES.ADMINS,
      },
    ],
  },
];

// Helper function to check if a route is active
export function isRouteActive(currentPath: string, targetPath?: string): boolean {
  if (!targetPath) return false;
  if (currentPath === targetPath) return true;
  // Check if current path starts with target path (for nested routes)
  return currentPath.startsWith(targetPath + '/');
}
