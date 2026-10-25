import type { MenuItem } from '@/shared/types';

/**
 * Dynamic permissions (ADR-0013): a permission is the name of an admin route, `<feature>.<action>`,
 * and the feature is the last segment of the resource endpoint (`/admin/learning-goal` → `learning-goal`).
 */
export const PERMISSION_ACTIONS = {
  LIST: 'list',
  STORE: 'store',
  UPDATE: 'update',
  DELETE: 'delete',
} as const;

export type PermissionAction = (typeof PERMISSION_ACTIONS)[keyof typeof PERMISSION_ACTIONS];

/** Separates the feature from the action, as `PermissionConst::SEPARATOR` does on the API. */
const SEPARATOR = '.';

export function permissionName(feature: string, action: string): string {
  return `${feature}${SEPARATOR}${action}`;
}

export function featureOfEndpoint(endpoint: string): string {
  return endpoint.split('/').filter(Boolean).pop() ?? '';
}

/** The action part of a permission name: `article.store` → `store`. */
export function actionOfPermission(name: string): string {
  return name.slice(name.indexOf(SEPARATOR) + 1);
}

/** Menu entries the admin may open; a group without any allowed entry disappears. */
export function filterMenu(items: MenuItem[], can: (permission: string) => boolean): MenuItem[] {
  return items.flatMap((item) => {
    if (item.children) {
      const children = filterMenu(item.children, can);
      return children.length > 0 ? [{ ...item, children }] : [];
    }
    return !item.permission || can(item.permission) ? [item] : [];
  });
}
