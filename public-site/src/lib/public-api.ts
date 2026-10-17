// Server-side client for the public API (docs/features/trang-public.md).
// Types come from laravel-api/openapi.json (`pnpm gen:api`, run by `make openapi`).
import type { components, paths } from '@/types/openapi';

// Inside Docker the browser URL (localhost:81) is not reachable, so the server calls nginx on the
// Compose network (unprivileged nginx listens on 8080 inside).
const DEFAULT_API_URL = 'http://ml-nginx:8080/api';
const API_URL = process.env.API_INTERNAL_URL ?? DEFAULT_API_URL;

// The public API is rate-limited per IP and every request comes from this server's IP,
// so answers are kept for a minute instead of fetched once per visitor.
const CACHE_MS = 60_000;

const HTTP_NOT_FOUND = 404;

export const MAX_LEVEL = 4;

type Data<P extends keyof paths> = paths[P] extends {
  get: { responses: { 200: { content: { 'application/json': { data: infer D } } } } };
}
  ? D
  : never;

export type PublicSkill = components['schemas']['PublicSkillResource'];
export type PublicSkillDetail = components['schemas']['PublicSkillDetailResource'];
export type PublicFile = PublicSkillDetail['evidence'][number]['files'][number];

const cache = new Map<string, { at: number; data: unknown }>();

// null when the API answers 404 (private or missing: the API does not tell them apart).
async function getPublic<T>(path: string): Promise<T | null> {
  const hit = cache.get(path);
  if (hit && Date.now() - hit.at < CACHE_MS) {
    return hit.data as T | null;
  }
  const response = await fetch(`${API_URL}/public/${path}`, {
    headers: { Accept: 'application/json' },
  });
  if (!response.ok && response.status !== HTTP_NOT_FOUND) {
    throw new Error(`Public API ${path} answered ${response.status}`);
  }
  const data =
    response.status === HTTP_NOT_FOUND ? null : ((await response.json()) as { data: T }).data;
  cache.set(path, { at: Date.now(), data });
  return data;
}

export async function getPublicSkills(): Promise<PublicSkill[]> {
  return (await getPublic<Data<'/public/skills'>>('skills'))?.data ?? [];
}

export async function getPublicSkill(slug: string): Promise<PublicSkillDetail | null> {
  return (
    (await getPublic<Data<'/public/skills/{slug}'>>(`skills/${encodeURIComponent(slug)}`))?.data ??
    null
  );
}

// Short-lived MinIO link of an attached file; null when the file is not on the public ledger.
// Kept for less time than the link lives (5 minutes for a download, 1 hour for a preview).
export async function getPublicFileUrl(key: string): Promise<string | null> {
  return (
    (await getPublic<Data<'/public/files/{key}'>>(`files/${encodeURIComponent(key)}`))?.url ?? null
  );
}
