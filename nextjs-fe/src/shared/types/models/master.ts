/**
 * Master data models, as the list endpoints return them.
 * Generated from laravel-api/openapi.json (`pnpm gen:api`); do not hand-edit fields here.
 */
import type { components, operations } from '@/shared/types/openapi';
import type { OkData } from './ok-data';

type Schemas = components['schemas'];

export type AdminMst = Schemas['AdminMstResource'];
export type Role = Schemas['RoleResource'];

/** The signed-in admin with the permission names it holds (`credential/me`, ADR-0013). */
export type CurrentAdmin = OkData<operations['credential.me']>;

/** Every permission, grouped by feature (`permission/list`). */
export type PermissionGroup = OkData<operations['permission.list']>[number];
