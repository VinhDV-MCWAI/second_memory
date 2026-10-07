/**
 * Master data models, as the list endpoints return them.
 * Generated from laravel-api/openapi.json (`pnpm gen:api`); do not hand-edit fields here.
 */
import type { components } from '@/shared/types/openapi';

type Schemas = components['schemas'];

export type AdminMst = Schemas['AdminMstResource'];
export type RoleMst = Schemas['RoleMstResource'];
export type DepartmentMst = Schemas['DepartmentMstResource'];
export type ApiMst = Schemas['ApiMstResource'];
export type FeatureMst = Schemas['FeatureMstResource'];
export type TokenMst = Schemas['TokenMstResource'];
export type PolicyDepartmentMst = Schemas['PolicyDepartmentMstResource'];

// Junction tables
export type AdminDepartmentMst = Schemas['AdminDepartmentMstResource'];
export type AdminRoleMst = Schemas['AdminRoleMstResource'];
export type ApiRoleMst = Schemas['ApiRoleMstResource'];
export type DepartmentManagementMst = Schemas['DepartmentManagementMstResource'];
