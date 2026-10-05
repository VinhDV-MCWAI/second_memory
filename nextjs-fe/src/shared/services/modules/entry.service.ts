import { ENDPOINTS } from '@/shared/api';
import type { EntryMgmt } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const entryService = createCrudService<EntryMgmt>({
  endpoint: ENDPOINTS.MANAGEMENT.ENTRY,
});
