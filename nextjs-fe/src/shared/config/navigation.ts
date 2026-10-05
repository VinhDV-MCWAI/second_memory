import {
  LayoutDashboard,
  Users,
  Settings,
  FolderOpen,
  Database,
  Package,
  Shield,
  Building2,
  Code,
  Sparkles,
  Key,
  FileCheck,
  UserCog,
  Grid3x3,
  Briefcase,
  Layers,
  Image,
  Sliders,
  Share2,
  Link,
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
        label: 'entities.departments',
        icon: Building2,
        href: ADMIN_ROUTES.DEPARTMENTS,
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
      {
        label: 'entities.policyDepartments',
        icon: FileCheck,
        href: ADMIN_ROUTES.POLICY_DEPARTMENTS,
      },
    ],
  },
  {
    label: 'navigation.contentManagement',
    icon: Package,
    children: [
      {
        label: 'entities.users',
        icon: Users,
        href: ADMIN_ROUTES.USERS,
      },
      {
        label: 'entities.categories',
        icon: Grid3x3,
        href: ADMIN_ROUTES.CATEGORIES,
      },
      {
        label: 'entities.entries',
        icon: Briefcase,
        href: ADMIN_ROUTES.ENTRIES,
      },
      {
        label: 'entities.entryDescriptions',
        icon: Layers,
        href: ADMIN_ROUTES.ENTRY_DESCRIPTIONS,
      },
      {
        label: 'entities.banners',
        icon: Image,
        href: ADMIN_ROUTES.BANNERS,
      },
      {
        label: 'entities.sliders',
        icon: Sliders,
        href: ADMIN_ROUTES.SLIDERS,
      },
      {
        label: 'entities.socials',
        icon: Share2,
        href: ADMIN_ROUTES.SOCIALS,
      },
      {
        label: 'entities.settingLinks',
        icon: Link,
        href: ADMIN_ROUTES.SETTING_LINKS,
      },
    ],
  },
  {
    label: 'navigation.settings',
    icon: Settings,
    children: [
      {
        label: 'navigation.general',
        icon: Settings,
        href: ADMIN_ROUTES.SETTINGS_GENERAL,
      },
      {
        label: 'navigation.security',
        icon: Settings,
        href: ADMIN_ROUTES.SETTINGS_SECURITY,
      },
      {
        label: 'navigation.notifications',
        icon: Settings,
        href: ADMIN_ROUTES.SETTINGS_NOTIFICATIONS,
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

// Helper function to find active menu item
export function findActiveMenuItem(
  menuItems: MenuItem[],
  currentPath: string
): MenuItem | null {
  for (const item of menuItems) {
    if (item.href && isRouteActive(currentPath, item.href)) {
      return item;
    }
    if (item.children) {
      const activeChild = findActiveMenuItem(item.children, currentPath);
      if (activeChild) return activeChild;
    }
  }
  return null;
}
