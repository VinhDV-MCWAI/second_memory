/**
 * Background tasks (ADR-0014). Generated from laravel-api/openapi.json (`pnpm gen:api`).
 */
import type { components } from '@/shared/types/openapi';

/** Status of a background task; also the payload of the `background-task.finished` live event. */
export type BackgroundTask = components['schemas']['BackgroundTaskResource'];
