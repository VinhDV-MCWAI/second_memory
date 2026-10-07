import {
  LayoutDashboard,
  FolderOpen,
  Database,
  Shield,
  Code,
  Sparkles,
  Key,
  UserCog,
} from 'lucide-react';
import { MenuItem } from '@/shared/types';
import { ADMIN_ROUTES } from '@/shared/config';

export const NAVIGATION_MENU: MenuItem[] = [
  {
    label: 'navigation.dashboard',
    icon: LayoutDashboard,
    href: ADMIN_ROUTES.DASHBOARD,
  },
  {
    label: 'navigation.fileManager',
    icon: FolderOpen,
    href: ADMIN_ROUTES.FILE_MANAGER,
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
      {
        label: 'entities.roles',
        icon: Shield,
        href: ADMIN_ROUTES.ROLES,
      },
      {
        label: 'entities.apis',
        icon: Code,
        href: ADMIN_ROUTES.APIS,
      },
      {
        label: 'entities.features',
        icon: Sparkles,
        href: ADMIN_ROUTES.FEATURES,
      },
      {
        label: 'entities.tokens',
        icon: Key,
        href: ADMIN_ROUTES.TOKENS,
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
