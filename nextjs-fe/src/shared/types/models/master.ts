/**
 * Master data models, as the list endpoints return them.
 * Generated from laravel-api/openapi.json (`pnpm gen:api`); do not hand-edit fields here.
 */
import type { components } from '@/shared/types/openapi';

type Schemas = components['schemas'];

export type AdminMst = Schemas['AdminMstResource'];
export type RoleMst = Schemas['RoleMstResource'];
export type ApiMst = Schemas['ApiMstResource'];
export type FeatureMst = Schemas['FeatureMstResource'];
export type TokenMst = Schemas['TokenMstResource'];

// Junction tables
export type AdminRoleMst = Schemas['AdminRoleMstResource'];
export type ApiRoleMst = Schemas['ApiRoleMstResource'];
