/**
 * Skill Ledger models, as the list endpoints return them (RFC-002, ADR-0008).
 * Generated from laravel-api/openapi.json (`pnpm gen:api`); do not hand-edit fields here.
 */
import type { components } from '@/shared/types/openapi';

type Schemas = components['schemas'];

export type Skill = Schemas['SkillResource'];
export type Tag = Schemas['TagResource'];
export type SkillLevelEntry = Schemas['SkillLevelResource'];
