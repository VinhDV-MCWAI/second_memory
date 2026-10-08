/**
 * Audit log rows (ADR-0006): one entry per change, with only the changed fields for updates.
 * Generated from laravel-api/openapi.json (`pnpm gen:api`).
 */
import type { components } from '@/shared/types/openapi';

export type AuditLogEntry = components['schemas']['AuditLogResource'];
