import { ENDPOINTS } from '@/shared/api';
import type { RoleMst } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const roleService = createCrudService<RoleMst>({
  endpoint: ENDPOINTS.MASTER.ROLE,
});
