import { ENDPOINTS } from '@/shared/api';
import type { ApiMst } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const apiService = createCrudService<ApiMst>({
  endpoint: ENDPOINTS.MASTER.API,
});
