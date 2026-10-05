import { ENDPOINTS } from '@/shared/api';
import type { UserMgmt } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const userService = createCrudService<UserMgmt>({
  endpoint: ENDPOINTS.MANAGEMENT.USER,
});
