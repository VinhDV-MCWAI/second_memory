import { ENDPOINTS } from '@/shared/api';
import type { EntryDescriptionMgmt } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const entryDescriptionService = createCrudService<EntryDescriptionMgmt>({
  endpoint: ENDPOINTS.MANAGEMENT.ENTRY_DESCRIPTION,
  useSuffix: true,
});
