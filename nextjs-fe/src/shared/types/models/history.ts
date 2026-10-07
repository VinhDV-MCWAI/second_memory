/**
 * History (`*-hist`) list rows: a snapshot of the audited record plus these audit fields.
 * Generated from laravel-api/openapi.json (`pnpm gen:api`); every history resource shares them.
 */
import type { components } from '@/shared/types/openapi';

export type HistoryRecord = Pick<
  components['schemas']['AdminMstHistResource'],
  'id' | 'action' | 'author_id' | 'created_at'
>;
