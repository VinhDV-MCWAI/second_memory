import {
  Building2,
  LayoutDashboard,
  Database,
  ShieldCheck,
  FileCheck,
  FolderOpen,
  History,
  GraduationCap,
  Tags,
  Target,
  UserCog,
  Users,
} from 'lucide-react';
import { MenuItem } from '@/shared/types';
import { ADMIN_ROUTES } from '@/shared/config';

export const NAVIGATION_MENU: MenuItem[] = [
  {
    label: 'navigation.dashboard',
    icon: LayoutDashboard,
    href: ADMIN_ROUTES.DASHBOARD,
    permission: 'dashboard.summary',
  },
  {
    label: 'entities.files',
    icon: FolderOpen,
    href: ADMIN_ROUTES.FILES,
    permission: 'media.list',
  },
  {
    label: 'navigation.auditLog',
    icon: History,
    href: ADMIN_ROUTES.AUDIT_LOG,
    permission: 'audit-log.list',
  },
  {
    label: 'navigation.skillLedger',
    icon: GraduationCap,
    children: [
      {
        label: 'entities.skills',
        icon: GraduationCap,
        href: ADMIN_ROUTES.SKILLS,
        permission: 'skill.list',
      },
      {
        label: 'entities.evidenceMany',
        icon: FileCheck,
        href: ADMIN_ROUTES.EVIDENCE,
        permission: 'evidence.list',
      },
      {
        label: 'entities.goals',
        icon: Target,
        href: ADMIN_ROUTES.GOALS,
        permission: 'learning-goal.list',
      },
      {
        label: 'entities.tags',
        icon: Tags,
        href: ADMIN_ROUTES.TAGS,
        permission: 'tag.list',
      },
    ],
  },
  {
    label: 'navigation.masterData',
    icon: Database,
    children: [
      {
        label: 'entities.admins',
        icon: UserCog,
        href: ADMIN_ROUTES.ADMINS,
        permission: 'admin-mst.list',
      },
      {
        label: 'entities.roles',
        icon: ShieldCheck,
        href: ADMIN_ROUTES.ROLES,
        permission: 'role.list',
      },
      {
        label: 'entities.departments',
        icon: Building2,
        href: ADMIN_ROUTES.DEPARTMENTS,
        permission: 'department.list',
      },
      {
        label: 'entities.endUsers',
        icon: Users,
        href: ADMIN_ROUTES.END_USERS,
        permission: 'end-user.list',
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
