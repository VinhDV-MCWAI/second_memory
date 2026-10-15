'use client';

import { useAuth } from '@/providers/use-auth';

/**
 * What the signed-in admin may do, from the permission names `credential/me` returns (ADR-0013).
 * Only hides what the API would refuse with 403; the API stays the real check.
 */
export function usePermissions() {
  const { user } = useAuth();
  const granted = new Set(user?.permissions ?? []);

  return { can: (permission: string) => granted.has(permission) };
}
