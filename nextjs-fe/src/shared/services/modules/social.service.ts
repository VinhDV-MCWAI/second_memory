import { ENDPOINTS } from '@/shared/api';
import type { SocialMgmt } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const socialService = createCrudService<SocialMgmt>({
  endpoint: ENDPOINTS.MANAGEMENT.SOCIAL,
});
