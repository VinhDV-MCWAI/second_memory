/**
 * Skill Ledger models, as the list endpoints return them (RFC-002, ADR-0008).
 * Generated from laravel-api/openapi.json (`pnpm gen:api`); do not hand-edit fields here.
 */
import type { components, operations } from '@/shared/types/openapi';

type Schemas = components['schemas'];

export type Skill = Schemas['SkillResource'];
export type Tag = Schemas['TagResource'];
export type SkillLevelEntry = Schemas['SkillLevelResource'];
export type Evidence = Schemas['EvidenceResource'];
export type LearningGoal = Schemas['LearningGoalResource'];

/** `data` of the JSON 200 response of a generated operation (the envelope's payload). */
type OkData<Operation> = Operation extends {
  responses: { 200: { content: { 'application/json': { data: infer Data } } } };
}
  ? Data
  : never;

export type DashboardSummary = OkData<operations['dashboard.summary']>;
export type SearchResult = OkData<operations['search.search']>;
