// Server-side client for the public Skill Ledger API (RFC-002 §4.4).
// Types mirror the `PublicSkillResource` / `PublicSkillDetailResource` schemas in laravel-api/openapi.json.
import 'server-only';

// Inside Docker the browser URL (localhost:81) is not reachable, so the server calls nginx on the
// Compose network (unprivileged nginx listens on 8080 inside since P4-02).
const DEFAULT_API_URL = 'http://ml-nginx:8080/api';
const API_URL = process.env.API_INTERNAL_URL ?? DEFAULT_API_URL;

// The public API is rate-limited per IP and every request comes from this server's IP,
// so responses are cached instead of fetched once per visitor.
const REVALIDATE_SECONDS = 60;

const HTTP_NOT_FOUND = 404;

export const EVIDENCE_TYPES = ['pr', 'adr', 'incident', 'note', 'other'] as const;
export type EvidenceType = (typeof EVIDENCE_TYPES)[number];

export const MAX_LEVEL = 4;

export interface PublicSkill {
  name: string;
  slug: string;
  category: string;
  current_level: number;
  current_level_label: string;
  tags: string[];
}

export interface PublicSkillDetail extends PublicSkill {
  description: string | null;
  history: { level: number; level_label: string; changed_on: string }[];
  evidence: {
    type: EvidenceType;
    title: string;
    url: string;
    occurred_on: string;
    summary: string | null;
  }[];
}

// Backend envelope: { data, error }; resources are wrapped once more in `data`.
interface Envelope<T> {
  data: { data: T };
}

async function getPublic<T>(path: string): Promise<T | null> {
  const response = await fetch(`${API_URL}/public/${path}`, {
    headers: { Accept: 'application/json' },
    next: { revalidate: REVALIDATE_SECONDS },
  });
  if (response.status === HTTP_NOT_FOUND) {
    return null;
  }
  if (!response.ok) {
    throw new Error(`Public API ${path} answered ${response.status}`);
  }
  const body = (await response.json()) as Envelope<T>;
  return body.data.data;
}

export async function getPublicSkills(): Promise<PublicSkill[]> {
  return (await getPublic<PublicSkill[]>('skills')) ?? [];
}

// null when the skill is private or does not exist (the API answers 404 for both).
export function getPublicSkill(slug: string): Promise<PublicSkillDetail | null> {
  return getPublic<PublicSkillDetail>(`skills/${encodeURIComponent(slug)}`);
}
